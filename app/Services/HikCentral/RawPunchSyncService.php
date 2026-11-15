<?php

namespace App\Services\HikCentral;

use App\Models\HikCentralPunchLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RawPunchSyncService
{
    public function __construct(
        protected HikCentralClient $client
    ) {}

    public function sync(Carbon $start, Carbon $end): int
    {
        if ($start->diffInDays($end) > 31) {
            throw new RuntimeException(
                'Raw punch search cannot exceed 31 days.'
            );
        }

        $page = 1;
        $processed = 0;

        do {
            $payload = [
                'startTime' => $start->toIso8601String(),
                'endTime' => $end->toIso8601String(),
                'eventType' => (int) config(
                    'hikcentral.event_type'
                ),
                'doorIndexCodes' => config(
                    'hikcentral.door_codes'
                ),
                'pageNo' => $page,
                'pageSize' => min(
                    500,
                    config('hikcentral.page_size', 100)
                ),
                'sortField' => 'SwipeTime',
                'orderType' => 0,
            ];

            $response = $this->client->post(
                '/artemis/api/acs/v1/door/events',
                $payload
            );

            $records = data_get(
                $response,
                'data.list',
                []
            );

            foreach ($records as $record) {
                if (empty($record['eventId'])) {
                    continue;
                }

                // HikCentralPunchLog::updateOrCreate(
                //     [
                //         'event_id' => (string)
                //             $record['eventId'],
                //     ],
                //     [
                //         'person_id' => $record['personId'] ?? null,

                //         'employee_name' =>
                //             $record['personName'] ?? null,

                //         'door_index_code' =>
                //             $record['doorIndexCode'] ?? null,

                //         'punch_time' => Carbon::parse(
                //             $record['eventTime'],
                //             config('hikcentral.timezone')
                //         )->utc(),

                //         'event_type' =>
                //             $record['eventType'] ?? null,

                //         'authentication_method' =>
                //             $record['authenticationMethod']
                //             ?? $record['verifyMode']
                //             ?? null,

                //         'raw_data' => $record,
                //     ]
                // );

                $processed++;
            }

            $total = (int) data_get(
                $response,
                'data.total',
                0
            );

            $pageSize = max(
                1,
                (int) data_get(
                    $response,
                    'data.pageSize',
                    100
                )
            );

            $hasMore = ($page * $pageSize) < $total;

            $page++;

        } while ($hasMore);

        return $processed;
    }
}
