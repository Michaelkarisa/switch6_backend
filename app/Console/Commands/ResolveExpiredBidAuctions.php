<?php

namespace App\Console\Commands;

use App\Services\MatchBidService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Resolves bid auctions whose 1-hour bidding window has closed: the
 * current top bids win their slots, the rest lose. Applies uniformly to
 * before_match, halftime, and fulltime — the window is always 1 hour from
 * the first bid on that match+period, not tied to kickoff.
 */
class ResolveExpiredBidAuctions extends Command
{
    protected $signature = 'bids:resolve-expired';

    protected $description = 'Resolve bid auctions whose 1-hour bidding window has closed.';

    public function handle(MatchBidService $bids): int
    {
        $expired = $bids->expiredAuctions();

        if ($expired->isEmpty()) {
            $this->info('No expired auctions to resolve.');

            return self::SUCCESS;
        }

        foreach ($expired as $row) {
            $result = $bids->resolveSlots($row->match_id, $row->period);
            $this->line("Match {$row->match_id} ({$row->period}): won={$result['won']} lost={$result['lost']}");
        }

        Log::info('bids:resolve-expired completed', ['auctions' => $expired->count()]);

        return self::SUCCESS;
    }
}
