<?php

namespace App\Services\HikCentral;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    protected HikCentralClient $client;

    public function __construct(HikCentralClient $client)
    {
        $this->client = $client;
    }

    public function sync(): int
    {
        $page = 1;
        $pageSize = 100;
        $processed = 0;
        do {
            $payload = [
                'pageNo' => $page,
                'pageSize' => $pageSize,
            ];

            $response = $this->client->post('/artemis/api/resource/v1/person/personList', $payload);

            $data = $response['data'] ?? [];
            $employees = $data['list'] ?? [];

            DB::transaction(function () use ($employees, &$processed) {
                foreach ($employees as $person) {
                    $personId = $person['personId'] ?? null;

                    if (!$personId) {
                        continue;
                    }

                    Employee::updateOrCreate(
                        [
                            'hikcentral_person_id' => $personId,
                        ],
                        [
                            'employee_code' => $person['personCode'] ?? null,
                            'name' => $person['personName'] ?? null,
                            'phone' => $person['phoneNo'] ?? null,
                            'email' => $person['email'] ?? null,
                            'organization_code' => $person['orgIndexCode'] ?? null,
                            'raw_data' => $person,
                            'last_synced_at' => now(),
                        ]
                    );

                    $processed++;
                }
            });

            $total = (int) ($data['total'] ?? 0);

            if (empty($employees)) {
                break;
            }

            $hasMorePages = $page * $pageSize < $total;
            $page++;

        } while ($hasMorePages);

        return $processed;
    }
}
