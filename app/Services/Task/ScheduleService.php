<?php

namespace App\Services\Task;

use App\Services\ApiManager\Operations\SrOperationsService;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleService
{
    const SCHEDULE_EVERY_MINUTE = 'every_minute';
    const SCHEDULE_EVERY_HOUR = 'every_hour';
    const SCHEDULE_EVERY_DAY = 'every_day';
    const SCHEDULE_EVERY_WEEK = 'every_weekday';
    const SCHEDULE_EVERY_MONTH = 'every_month';

    const SCHEDULE_INTERVALS = [
        self::SCHEDULE_EVERY_MINUTE => [
            'field' => 'every_minute',
            'method' => 'everyMinute'
        ],
        self::SCHEDULE_EVERY_HOUR => [
            'field' => 'every_hour',
            'method' => 'hourly'
        ],
        self::SCHEDULE_EVERY_DAY => [
            'field' => 'every_day',
            'method' => 'daily'
        ],
        self::SCHEDULE_EVERY_WEEK => [
            'field' => 'every_weekday',
            'method' => 'weekly'
        ],
        self::SCHEDULE_EVERY_MONTH => [
            'field' => 'every_month',
            'method' => 'monthly'
        ],
    ];

    private Schedule $schedule;
    private SrOperationsService $providerEventsService;

    public function __construct(SrOperationsService $providerEventsService)
    {
        $this->providerEventsService = $providerEventsService;
    }

    public function run(): void
    {
        foreach (self::SCHEDULE_INTERVALS as $interval) {
            $method = $interval['method'];
            $this->schedule->call(
                function () use ($interval){
                    $this->providerEventsService->providerSrSchedule($interval);
                },
            )
                ->{$method}();
        }
    }

    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

}
