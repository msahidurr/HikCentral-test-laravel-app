<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HikCentral\AttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHikCentralAttendance extends Command
{
    protected $signature = 'hikcentral:sync-attendance';

    protected $description = 'Sync HikCentral attendance reports';

    public function handle(AttendanceSyncService $service)
    {
        try {
            // Re-fetch recent days because HikCentral
            // attendance calculations can be updated.
            for ($i = 1; $i <= 7; $i++) {
                $date = Carbon::today(
                    config('hikcentral.timezone')
                )->subDays($i);

                $count = $service->sync($date);

                $this->info(
                    $date->toDateString() .
                    ": {$count} attendance records"
                );
            }

        } catch (\Throwable $e) {
            Log::error('HikCentral attendance sync failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
