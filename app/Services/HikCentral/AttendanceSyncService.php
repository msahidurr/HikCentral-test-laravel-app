<?php

namespace App\Services\HikCentral;

use App\Models\HikCentralAttendance;
use Carbon\Carbon;
use RuntimeException;

class AttendanceSyncService
{
    public function __construct(
        protected HikCentralClient $client
    ) {}

    public function sync(
        Carbon $date
    ): int {
        $page = 1;
        $processed = 0;

        do {
            $day = $date->format('Y-m-d');

            $payload = [
                'attendanceReportRequest' => [
                    'pageNo' => $page,
                    'pageSize' => 100,
                    'queryInfo' => [
                        'personID' => [],

                        'beginTime' =>
                            $day . 'T00:00:00 06:00',

                        'endTime' =>
                            $day . 'T23:59:59 06:00',

                        'sortInfo' => [
                            'sortField' => 1,
                            'sortType' => 1,
                        ],
                    ],
                ],
            ];

            $response = $this->client->post(
                '/artemis/api/attendance/v1/report',
                $payload
            );

            $records = data_get(
                $response,
                'data.record',
                []
            );

            foreach ($records as $record) {
                $person = $record['personInfo'] ?? [];

                $personId = $person['personID'] ?? null;

                if (!$personId) {
                    continue;
                }

                $base = $record['attendanceBaseInfo'] ?? [];
                $plan = $record['planInfo'] ?? [];

                // HikCentralAttendance::updateOrCreate(
                //     [
                //         'person_id' => (string) $personId,
                //         'attendance_date' => $record['date'],
                //     ],
                //     [
                //         'employee_code' =>
                //             $person['personCode'] ?? null,

                //         'employee_name' =>
                //             $person['fullName'] ?? null,

                //         'department_id' =>
                //             $person['orgIndexCode'] ?? null,

                //         'attendance_status' =>
                //             $base['attendanceStatus'] ?? null,

                //         'planned_start' =>
                //             $plan['planBeginTime'] ?? null,

                //         'planned_end' =>
                //             $plan['planEndTime'] ?? null,

                //         'actual_start' =>
                //             $base['beginTime'] ?: null,

                //         'actual_end' =>
                //             $base['endTime'] ?: null,

                //         'total_duration' => (int) (
                //             $record['allDurationTime'] ?? 0
                //         ),

                //         'normal_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'normalInfo.durationTime',
                //                 0
                //             )
                //         ),

                //         'late_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'lateInfo.durationTime',
                //                 0
                //             )
                //         ),

                //         'early_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'earlyInfo.durationTime',
                //                 0
                //             )
                //         ),

                //         'absence_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'absenceInfo.durationTime',
                //                 0
                //             )
                //         ),

                //         'overtime_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'overtimeInfo.allOvertimeDurationTime',
                //                 0
                //             )
                //         ),

                //         'leave_duration' => (int) (
                //             data_get(
                //                 $record,
                //                 'leaveInfo.allLeaveDurationTime',
                //                 0
                //             )
                //         ),

                //         'raw_data' => $record,
                //     ]
                // );

                $processed++;
            }

            $hasMore = (string) data_get(
                $response,
                'data.nextPage',
                '0'
            ) === '1';

            $page++;

        } while ($hasMore);

        return $processed;
    }
}
