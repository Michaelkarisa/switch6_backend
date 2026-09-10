<?php

namespace App\Console\Commands;

use App\Models\MatchBid;
use App\Models\MatchModel;
use App\Services\MatchBidService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Auto-resolves "before_match" bid slot auctions shortly before kickoff,
 * so winning ads are known in time to play as pre-roll. halftime/fulltime
 * auctions are resolved live via MatchBidController::resolve (they depend
 * on the match's actual live clock, not a fixed schedule).
 */
class ResolveBeforeMatchBidSlots extends Command
{
    protected $signature = 'bids:resolve-before-match {--minutes=30 : Resolve auctions for matches kicking off within this many minutes}';

    protected $description = 'Resolve before_match bid slot auctions for matches about to kick off.';

    public function handle(MatchBidService $bids): int
    {
        $minutes = (int) $this->option('minutes');

        $matchIds = MatchModel::whereIn('status', ['scheduled', 'live'])
            ->whereBetween('match_date', [now(), now()->addMinutes($minutes)])
            ->whereHas('bids', fn ($q) => $q->where('period', 'before_match')->where('status', 'pending'))
            ->pluck('id');

        if ($matchIds->isEmpty()) {
            $this->info('No before_match auctions to resolve.');

            return self::SUCCESS;
        }

        foreach ($matchIds as $matchId) {
            $result = $bids->resolveSlots($matchId, 'before_match');
            $this->line("Match {$matchId}: won={$result['won']} lost={$result['lost']}");
        }

        Log::info('bids:resolve-before-match completed', ['matches' => $matchIds->count()]);

        return self::SUCCESS;
    }
}
