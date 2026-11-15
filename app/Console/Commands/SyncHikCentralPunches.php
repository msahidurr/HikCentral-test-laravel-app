<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HikCentral\RawPunchSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHikCentralPunches extends Command
{
    protected $signature = 'hikcentral:sync-punches';

    protected $description = 'Sync raw HikCentral punch records';

    public function handle(RawPunchSyncService $service)
    {
        try {
            $end = now('UTC');

            // Overlap previous records to avoid missing
            // delayed events. event_id prevents duplicates.
            $start = $end->copy()->subMinutes(10);

            $count = $service->sync($start, $end);

            $this->info("Synced {$count} punch records.");

            Log::info('HikCentral punch sync completed', [
                'count' => $count,
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {
            Log::error('HikCentral punch sync failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
