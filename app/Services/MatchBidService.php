<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\MatchBid;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bid-tab: advertisers bid for one of 5 ad slots per period
 * (before_match | halftime | fulltime) on a given match. Highest bids win;
 * the rest lose. Matches are ranked/searchable by traction (views), date,
 * stadium (venue), or club.
 */
class MatchBidService
{
    public function __construct(
        private AuditLogService $audit,
        private PaymentService  $payment,
        private AdPricingService $pricing,
        private MatchRevenueService $revenue,
    ) {}

    /**
     * Matches open for bidding, ranked by traction (cumulative views) with
     * optional filters. This is what powers "at bid tab will show all
     * matches ranked top. can search by date time, stadium(ground) or club."
     */
    public function eligibleMatches(array $filters): Collection
    {
        $query = MatchModel::query()
            ->with(['homeClub', 'awayClub'])
            ->whereIn('status', ['scheduled', 'live']);

        if (! empty($filters['date'])) {
            $query->whereDate('match_date', $filters['date']);
        }

        if (! empty($filters['stadium'])) {
            $query->where('venue', 'like', '%'.$filters['stadium'].'%');
        }

        if (! empty($filters['club_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('home_club_id', $filters['club_id'])
                    ->orWhere('away_club_id', $filters['club_id']);
            });
        }

        return $query->get()
            ->sortByDesc(fn (MatchModel $match) => $match->matchViews())
            ->values();
    }

    public function basePrice(string $period): int
    {
        return $this->pricing->basePriceForPeriod($period);
    }

    /**
     * Create a bid campaign: one advertisement plus one match_bids row per
     * (match, period) being bid on, covered by a single payment for the
     * sum of every bid amount.
     *
     * $data['bids'] = [['match_id' => ..., 'period' => ..., 'amount' => ...], ...]
     */
    public function createBidCampaign(User $user, array $data): array
    {
        $this->pricing->assertBidFileTypeAllowed($data['file_type']);

        if (empty($data['bids'])) {
            throw ValidationException::withMessages([
                'bids' => ['At least one match bid is required.'],
            ]);
        }

        $total = 0;
        foreach ($data['bids'] as $bid) {
            $base = $this->pricing->basePriceForPeriod($bid['period']);

            if ((int) $bid['amount'] < $base) {
                throw ValidationException::withMessages([
                    'bids' => ["Bid for match {$bid['match_id']} ({$bid['period']}) must be at least KES {$base}."],
                ]);
            }

            $total += (int) $bid['amount'];
        }

        return DB::transaction(function () use ($user, $data, $total) {
            $ad = Advertisement::create([
                'user_id'        => $user->id,
                'title'          => $data['title'],
                'file_type'      => $data['file_type'],
                'file_path'      => $this->resolveFilePath($data),
                'duration'       => (int) config('ads.default_image_seconds', 10),
                'status'         => 'pending', // active once payment confirmed
                'campaign_type'  => 'bid',
                'target_tags'    => isset($data['target_tags']) ? (array) $data['target_tags'] : null,
                'price'          => $total,
                'broadcaster_id' => (! empty($data['self_advertise']) && $user->hasRole('broadcaster'))
                    ? $user->id
                    : null,
            ]);

            $paymentId = $this->payment->pay($user, [
                'amount'   => $total,
                'currency' => $data['currency'] ?? 'KES',
                'details'  => $data['details'] ?? [],
                'method'   => $data['method'] ?? 'mpesa',
                'type'     => 'advertisement',
            ]);

            if (! $paymentId) {
                throw ValidationException::withMessages([
                    'payment' => ['Could not initiate payment. Please try again.'],
                ]);
            }

            $ad->update(['payment_id' => $paymentId]);

            foreach ($data['bids'] as $bid) {
                MatchBid::create([
                    'match_id'         => $bid['match_id'],
                    'advertisement_id' => $ad->id,
                    'user_id'          => $user->id,
                    'payment_id'       => $paymentId,
                    'period'           => $bid['period'],
                    'amount'           => $bid['amount'],
                    'currency'         => $data['currency'] ?? 'KES',
                    'status'           => 'pending_payment',
                ]);
            }

            $this->audit->log(
                'bid_campaign_created',
                'match_bids',
                'Bid campaign created',
                ['ad_id' => $ad->id, 'total' => $total, 'bids' => count($data['bids'])],
                $ad,
                userId: $user->id,
            );

            return [
                'advertisement' => $ad->fresh(),
                'payment_id'    => $paymentId,
                'total'         => $total,
            ];
        });
    }

    private function resolveFilePath(array $data): string
    {
        if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            return $data['file']->store('ads', 'public');
        }

        return $data['file_path'] ?? '';
    }

    public function myBids(User $user): Collection
    {
        return MatchBid::with(['match.homeClub', 'match.awayClub', 'ad'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    /**
     * Resolve the auction for a match+period: the top N (config
     * ads.slots_per_period) *paid* bids win the slots, ranked 1..N by
     * amount; the rest lose.
     */
    public function resolveSlots(string $matchId, string $period): array
    {
        $slots = (int) config('ads.slots_per_period', 5);

        return DB::transaction(function () use ($matchId, $period, $slots) {
            $bids = MatchBid::forPeriod($matchId, $period)
                ->where('status', 'pending') // only paid bids compete
                ->orderByDesc('amount')
                ->orderBy('created_at')
                ->get();

            $winners = $bids->take($slots)->values();
            $losers = $bids->slice($slots);

            $winners->each(function (MatchBid $bid, int $index) {
                $bid->update(['status' => 'won', 'slot_rank' => $index + 1]);
            });

            $losers->each(fn (MatchBid $bid) => $bid->update(['status' => 'lost']));

            if ($winners->isNotEmpty()) {
                $this->audit->log(
                    'slots_resolved',
                    'match_bids',
                    "Bid slots resolved for match {$matchId} ({$period})",
                    ['won' => $winners->count(), 'lost' => $losers->count()],
                );

                $this->revenue->recomputeForMatch(MatchModel::find($matchId));
            }

            return ['won' => $winners->count(), 'lost' => $losers->count()];
        });
    }
}
