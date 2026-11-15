<?php

namespace App\Http\Controllers;

use App\Services\HikCentral\EmployeeService;
use App\Services\HikCentral\HikCentralClient;
use App\Services\HikCentral\AttendanceEventService;

class HikCentralController extends Controller
{
    public function test(HikCentralClient $client)
    {
        return response()->json(
            $client->post('/artemis/api/common/v1/version', [])
        );
    }

    public function fetchAttendanceEvents(AttendanceEventService $service)
    {
        return response()->json(
            $service->sync(
                now()->startOfDay(),
                now()->endOfDay()
            )
        );
    }

    public function fetchEmployees(EmployeeService $service)
    {
        return response()->json(
            $service->sync()
        );
    }
}
