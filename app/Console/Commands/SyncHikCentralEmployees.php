<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HikCentral\EmployeeService;
use App\Models\HikcentralSyncLog;

class SyncHikCentralEmployees extends Command
{
    protected $signature = 'hikcentral:sync-employees';

    protected $description = 'Synchronize HikCentral employees';

    public function handle(EmployeeService $service)
    {
        $log = HikcentralSyncLog::create([
            'sync_type' => 'employees',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $started = now();

        try {
            $this->info("Employees synchronized started: {$started}");
            
            $count = $service->sync();

            $this->info("Employees synchronized: {$count}");

            $log->update([
                'status' => 'success',
                'records_processed' => $count,
                'message' => 'Employee synchronization completed.',
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
