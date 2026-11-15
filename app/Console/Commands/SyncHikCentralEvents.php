<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\HikCentral\AttendanceEventService;
use App\Models\HikcentralSyncLog;

class SyncHikCentralEvents extends Command
{
    protected $signature = 'hikcentral:sync-events {--from= : Start datetime} {--to= : End datetime}';

    protected $description = 'Synchronize HikCentral access-control events';

    public function handle(AttendanceEventService $service)
    {
        $log = HikcentralSyncLog::create([
            'sync_type' => 'events',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $timezone = config('services.hikcentral.timezone', 'Asia/Dhaka');
            $end = $this->option('to') ? Carbon::parse($this->option('to'), $timezone) : now($timezone);
            $start = $this->option('from') ? Carbon::parse($this->option('from'), $timezone) : $end->copy()->subDay();
            $count = $service->sync($start, $end);
            $this->info("Attendance events synchronized: {$count}");

            $log->update([
                'status' => 'success',
                'records_processed' => $count,
                'message' => 'Event synchronization completed.',
                'finished_at' => now(),
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            
            $log->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return self::FAILURE;
        }
    }
}
