<?php

namespace App\Console\Commands;

use App\Models\AdEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneAdEvents extends Command
{
    protected $signature   = 'ads:prune-events
                                {--days=90  : Delete ad events older than this many days}
                                {--dry-run  : Count records without deleting}';

    protected $description = 'Delete old AdEvent records to keep the table from growing unbounded.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dry  = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $this->info("Pruning ad events older than {$days} days (before {$cutoff->toDateString()})" . ($dry ? ' [DRY RUN]' : ''));

        $query = AdEvent::where('created_at', '<', $cutoff);
        $count = $query->count();

        $this->line("Found {$count} ad event(s) eligible for pruning.");

        if ($count === 0) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->line("[DRY] Would delete {$count} ad event(s).");
            return self::SUCCESS;
        }

        // Delete in chunks to avoid long-running locks
        $deleted = 0;
        AdEvent::where('created_at', '<', $cutoff)
            ->chunkById(500, function ($chunk) use (&$deleted) {
                $ids = $chunk->pluck('id');
                AdEvent::whereIn('id', $ids)->delete();
                $deleted += $ids->count();
            });

        $this->line("Deleted {$deleted} ad event(s).");

        Log::info("ads:prune-events – deleted {$deleted} ad events.", [
            'cutoff_days' => $days,
            'cutoff_date' => $cutoff->toDateString(),
        ]);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
