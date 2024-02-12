<?php

namespace App\Services\Provider;

use App\Models\Sr;
use App\Models\SrSchedule;
use App\Services\ApiManager\Operations\SrOperationsService;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderEventService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class ProviderScheduleService
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
    private Carbon $today;

    public function __construct(
        private ProviderEventService $providerEventsService,
        private ProviderService      $providerService,
        private SrService            $srService,
        private SrScheduleService           $srScheduleService
    )
    {
        $this->today = now();
    }

    public function run(): void
    {
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $srs = $this->srService->getServiceRequestRepository()->findSrsWithSchedule($provider);
            if ($srs->count() === 0) {
                continue;
            }
            $this->runBatchSrs($srs);
        }
    }

    private function runBatchSrs(Collection $srs)
    {
        foreach ($srs as $serviceRequest) {
            $this->runScheduleForSr($serviceRequest);
        }
    }

    private function runScheduleForSr(Sr $sr)
    {
        $findParentChildSr = $this->srScheduleService->findScheduleForOperationBySr($sr);
        $isParentSr = $findParentChildSr['is_parent'];
        $schedule = $findParentChildSr['schedule'];

        if (!$schedule instanceof SrSchedule) {
            return;
        }
        if ($schedule->disabled && $schedule->disable_child_srs) {
            return;
        } elseif ($schedule->disabled && !$schedule->disable_child_srs) {
            $this->runChildSrSchedule($sr);
            return;
        }
        if (!empty($schedule->start_date) && $schedule->start_date <= $this->today->toDateString()) {
            return;
        }
        if (!empty($schedule->end_date) && $schedule->end_date >= $this->today->toDateString()) {
            return;
        }

        if ($schedule->every_minute) {
            $this->getSrScheduleCall($sr)->everyMinute();
            $this->runChildSrSchedule($sr);
        }
        if ($schedule->every_hour) {
            $scheduleCall = $this->getSrScheduleCall($sr);
            $minute = $schedule->minute;
            if (empty($minute)) {
                $minute = 0;
            }
            $scheduleCall->hourlyAt($minute);
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_day) {
            $this->getSrScheduleCall($sr)
                ->dailyAt($this->getHourMinutes($schedule));
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_weekday) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $weekday = $schedule->weekday;
            if (empty($weekday)) {
                $weekday = 1;
            }
            $this->getSrScheduleCall($sr)->weeklyOn($weekday, $hourMinutes);
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_month) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $day = $schedule->day;
            if (empty($day)) {
                $day = 1;
            }
            $this->getSrScheduleCall($sr)->monthlyOn($day, $hourMinutes);
            $this->runChildSrSchedule($sr);
        }

    }

    private function runChildSrSchedule(Sr $sr)
    {
        $childSrs = $sr->childSrs()->get();
        if ($childSrs->count() === 0) {
            return;
        }
        $this->runBatchSrs($childSrs);
    }

    private function getHourMinutes(SrSchedule $schedule)
    {
        $hour = $schedule->hour;
        $minute = $schedule->minute;
        if (empty($hour)) {
            $hour = '00';
        }
        if (empty($minute)) {
            $minute = '00';
        }
        return "$hour:$minute";
    }

    private function getSrScheduleCall(Sr $sr)
    {
        return $this->schedule->call(
            function () use ($sr) {
                $this->providerEventsService->dispatchSrOperationEvent($sr);
            },
        );
    }

    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

}
