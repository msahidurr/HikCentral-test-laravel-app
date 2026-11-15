<?php

namespace App\Services\HikCentral;

use Carbon\Carbon;
use App\Models\AttendanceEvent;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AttendanceEventService
{
    protected HikCentralClient $client;

    protected string $timezone;

    protected int $pageSize = 100;

    public function __construct(HikCentralClient $client)
    {
        $this->client = $client;

        $this->timezone = config('services.hikcentral.timezone','Asia/Dhaka');
    }

    /**
     * Synchronize attendance events from HikCentral.
     *
     * Fetches IN and OUT events separately based on configured event types.
     *
     * @param string|Carbon $startTime
     * @param string|Carbon $endTime
     * @return int Total number of events processed
     */
    public function sync($startTime, $endTime): int
    {
        $start = Carbon::parse($startTime, $this->timezone);
        $end = Carbon::parse($endTime, $this->timezone);

        $this->validateDateRange($start, $end);

        $eventTypeGroups = [
            'IN' => config('services.hikcentral.check_in_event_types',[]),
            'OUT' => config('services.hikcentral.check_out_event_types',[]),
        ];

        $doorIndexCodes = config('services.hikcentral.door_index_codes',[]);
        
        $this->validateConfiguration($eventTypeGroups, $doorIndexCodes);

        $processedCount = 0;

        $formattedStart = $start->format('Y-m-d\TH:i:s.vP');
        $formattedEnd = $end->format('Y-m-d\TH:i:s.vP');

        foreach ($eventTypeGroups as $direction => $eventTypes) {
            foreach ($eventTypes as $eventType) {
                $processedCount += $this->syncEventType((int) $eventType, $direction, $doorIndexCodes, $formattedStart, $formattedEnd);
            }
        }

        return $processedCount;
    }

    /**
     * Fetch and synchronize all pages for one event type.
     */
    protected function syncEventType(int $eventType, string $direction, array $doorIndexCodes, string $startTime, string $endTime): int
    {
        $pageNo = 1;
        $processedCount = 0;

        do {
            $payload = [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'eventType' => $eventType,
                'doorIndexCodes' => array_values($doorIndexCodes),
                'pageNo' => $pageNo,
                'pageSize' => $this->pageSize,
            ];

            $response = $this->client->post('/artemis/api/acs/v1/door/events', $payload);
            $data = $response['data'] ?? [];
            $events = $data['list'] ?? [];

            if (!is_array($events)) {
                throw new RuntimeException(
                    "Invalid event list returned for event type {$eventType}."
                );
            }

            if (empty($events)) {
                break;
            }

            DB::transaction(function () use ($events, $direction, $eventType) {
                foreach ($events as $event) {
                    $this->saveEvent($event, $direction, $eventType);
                }
            });

            $currentPageCount = count($events);
            $processedCount += $currentPageCount;

            $total = isset($data['total']) ? (int) $data['total'] : null;

            $pageNo++;

            // Continue while more records are available.
            if ($total !== null) {
                $hasMorePages = (($pageNo - 1) * $this->pageSize) < $total;
            } else {
                $hasMorePages = $currentPageCount === $this->pageSize;
            }

        } while ($hasMorePages);

        return $processedCount;
    }

    /**
     * Insert or update an attendance event.
     */
    protected function saveEvent(array $event,string $direction,int $configuredEventType): void
    {
        if (empty($event['eventId'])) {
            throw new RuntimeException(
                'HikCentral returned an event without eventId.'
            );
        }

        $eventTime = $event['eventTime'] ?? null;

        if (empty($eventTime)) {
            throw new RuntimeException(
                "Event {$event['eventId']} has no eventTime."
            );
        }

        $parsedEventTime = Carbon::parse($eventTime,$this->timezone);

        AttendanceEvent::updateOrCreate(
            [
                'hikcentral_event_id' => (string) $event['eventId'],
            ],
            [
                'person_id' => $event['personId'] ?? null,
                'employee_code' => $event['personCode'] ?? null,
                'person_name' => $event['personName'] ?? null,
                'event_type' => $event['eventType'] ?? $configuredEventType,
                'direction' => $direction,
                'event_time' => $parsedEventTime,
                'door_index_code' => $event['doorIndexCode'] ?? null,
                'door_name' => $event['doorName'] ?? null,
                'raw_data' => $event,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Validate the requested date range.
     */
    protected function validateDateRange(Carbon $start, Carbon $end): void 
    {
        if ($start->greaterThanOrEqualTo($end)) {
            throw new InvalidArgumentException(
                'Start time must be earlier than end time.'
            );
        }

        if ($start->diffInSeconds($end) > (31 * 24 * 60 * 60)) {
            throw new InvalidArgumentException(
                'HikCentral event queries cannot exceed 31 days.'
            );
        }
    }

    /**
     * Validate configured event types and door codes.
     */
    protected function validateConfiguration(array $eventTypeGroups, array $doorIndexCodes): void 
    {
        if (empty($doorIndexCodes)) {
            throw new InvalidArgumentException(
                'HikCentral door index codes are not configured.'
            );
        }

        if (count($doorIndexCodes) > 10) {
            throw new InvalidArgumentException(
                'Maximum 10 door index codes are allowed per request.'
            );
        }

        foreach ($doorIndexCodes as $doorCode) {
            if (trim((string) $doorCode) === '') {
                throw new InvalidArgumentException(
                    'Door index codes cannot contain empty values.'
                );
            }
        }

        $allEventTypes = [];

        foreach ($eventTypeGroups as $direction => $eventTypes) {
            if (!in_array($direction, ['IN', 'OUT'], true)) {
                throw new InvalidArgumentException(
                    "Unsupported attendance direction: {$direction}"
                );
            }

            if (!is_array($eventTypes) || empty($eventTypes)) {
                throw new InvalidArgumentException(
                    "No HikCentral event types configured for {$direction}."
                );
            }

            foreach ($eventTypes as $eventType) {
                $eventType = trim((string) $eventType);

                if ($eventType === '' || !ctype_digit($eventType)) {
                    throw new InvalidArgumentException(
                        "Invalid {$direction} event type: {$eventType}"
                    );
                }

                if (in_array($eventType, $allEventTypes, true)) {
                    throw new InvalidArgumentException(
                        "Event type {$eventType} is configured more than once."
                    );
                }

                $allEventTypes[] = $eventType;
            }
        }
    }
}
