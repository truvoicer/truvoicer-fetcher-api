<?php

namespace App\Services\Task;

use App\Services\Provider\ProviderEventService;
use App\Services\Provider\ProviderScheduleService;
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

    public function __construct(
        private ProviderScheduleService $providerScheduleService
    )
    {
    }

    public function run(): void
    {
        $this->providerScheduleService->setSchedule($this->schedule);
        $this->providerScheduleService->run();
    }

    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

}
