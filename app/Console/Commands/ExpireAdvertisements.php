<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireAdvertisements extends Command
{
    protected $signature   = 'ads:expire
                                {--dry-run : List expired ads without updating them}';

    protected $description = 'Mark advertisements whose end_date has passed as expired.';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $this->info('Running advertisement expiry check' . ($dry ? ' [DRY RUN]' : ''));

        $query = Advertisement::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today());

        $count = $query->count();
        $this->line("Found {$count} advertisement(s) past their end_date.");

        if ($count === 0) {
            $this->info('Nothing to expire.');
            return self::SUCCESS;
        }

        if ($dry) {
            $query->each(function (Advertisement $ad) {
                $this->line("  [DRY] Would expire: [{$ad->id}] {$ad->title} (end_date: {$ad->end_date})");
            });
            return self::SUCCESS;
        }

        $updated = Advertisement::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'expired']);

        $this->line("Marked {$updated} advertisement(s) as expired.");

        Log::info("ads:expire – marked {$updated} advertisements expired.", [
            'date' => today()->toDateString(),
        ]);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
