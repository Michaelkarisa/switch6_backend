<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\MatchBid;
use App\Models\MatchModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bid-tab: ADVERTISERS ONLY bid for one of 5 ad slots per period
 * (before_match | halftime | fulltime) on a given match. Broadcasters
 * cannot use this — they can only advertise on matches they created
 * themselves, which goes through the general-campaign self-advertise flow
 * instead (see AdvertisementService).
 *
 * Each match+period is a single ascending auction: the first bid must meet
 * the period's base price; every bid after that must exceed the current
 * highest paid bid. The auction opens a 1-hour window from the first bid;
 * once that window closes, the top N bids (by amount, i.e. the last N
 * distinct bids since they strictly increase) win the N slots. If nobody
 * ever bid on a slot, it simply falls through to general campaigns (see
 * AdTargetingService::getads()) — no explicit action needed for that case.
 */
class MatchBidService
{
    private const BIDDING_WINDOW_MINUTES = 60;

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
     * The amount currently required to place a winning bid for this
     * match+period: the period base price, or one more than the current
     * highest paid bid — whichever is greater.
     */
    public function minimumNextBid(string $matchId, string $period): int
    {
        return max(
            $this->pricing->basePriceForPeriod($period),
            $this->currentHighestBid($matchId, $period) + 1,
        );
    }

    public function currentHighestBid(string $matchId, string $period): int
    {
        return (int) (MatchBid::forPeriod($matchId, $period)
            ->whereIn('status', ['pending', 'won'])
            ->max('amount') ?? 0);
    }

    /**
     * When bidding closes for this match+period — 1 hour after the first
     * bid was ever placed on it. Null if nobody has bid on it yet.
     */
    public function biddingDeadline(string $matchId, string $period): ?Carbon
    {
        $firstBidAt = MatchBid::forPeriod($matchId, $period)->min('created_at');

        return $firstBidAt ? Carbon::parse($firstBidAt)->addMinutes(self::BIDDING_WINDOW_MINUTES) : null;
    }

    /**
     * Status snapshot for a match+period, used by the frontend to show the
     * current leading bid, the minimum needed to take the lead, and the
     * countdown.
     */
    public function auctionStatus(string $matchId, string $period): array
    {
        return [
            'base_price'         => $this->pricing->basePriceForPeriod($period),
            'current_highest'    => $this->currentHighestBid($matchId, $period),
            'minimum_next_bid'   => $this->minimumNextBid($matchId, $period),
            'deadline'           => $this->biddingDeadline($matchId, $period)?->toIso8601String(),
            'bidding_closed'     => ($deadline = $this->biddingDeadline($matchId, $period)) && now()->greaterThan($deadline),
        ];
    }

    /**
     * Create a bid campaign: one advertisement plus one match_bids row per
     * (match, period) being bid on, covered by a single payment for the
     * sum of every bid amount. Advertisers only — broadcasters are
     * rejected here (they self-advertise via general campaigns instead).
     *
     * $data['bids'] = [['match_id' => ..., 'period' => ..., 'amount' => ...], ...]
     */
    public function createBidCampaign(User $user, array $data): array
    {
        if ($user->hasRole('broadcaster')) {
            throw ValidationException::withMessages([
                'bids' => ['Broadcasters can only advertise on matches they created — use the general campaign tab with self-advertise instead of the bid tab.'],
            ]);
        }

        $this->pricing->assertBidFileTypeAllowed($data['file_type']);

        if (empty($data['bids'])) {
            throw ValidationException::withMessages([
                'bids' => ['At least one match bid is required.'],
            ]);
        }

        $total = 0;
        foreach ($data['bids'] as $bid) {
            $deadline = $this->biddingDeadline($bid['match_id'], $bid['period']);

            if ($deadline && now()->greaterThan($deadline)) {
                throw ValidationException::withMessages([
                    'bids' => ["Bidding has closed for this match ({$bid['period']})."],
                ]);
            }

            $minRequired = $this->minimumNextBid($bid['match_id'], $bid['period']);

            if ((int) $bid['amount'] < $minRequired) {
                $highest = $this->currentHighestBid($bid['match_id'], $bid['period']);
                $reason = $highest > 0
                    ? "must outbid the current highest bid — at least KES {$minRequired}"
                    : "must be at least KES {$minRequired}";

                throw ValidationException::withMessages([
                    'bids' => ["Bid for match {$bid['match_id']} ({$bid['period']}) {$reason}."],
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
     * Every (match_id, period) pair with at least one paid bid still
     * awaiting resolution whose 1-hour bidding window has closed.
     */
    public function expiredAuctions(): Collection
    {
        return MatchBid::where('status', 'pending')
            ->select('match_id', 'period')
            ->distinct()
            ->get()
            ->filter(function ($row) {
                $deadline = $this->biddingDeadline($row->match_id, $row->period);

                return $deadline && now()->greaterThan($deadline);
            })
            ->values();
    }

    /**
     * Resolve the auction for a match+period: the top N (config
     * ads.slots_per_period) *paid* bids win the slots, ranked 1..N by
     * amount; the rest lose. Since bids strictly increase (each must
     * outbid the last), the top N by amount are simply the N most recent
     * qualifying bids.
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
