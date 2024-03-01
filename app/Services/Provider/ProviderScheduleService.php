<?php

namespace App\Services\Provider;

use App\Models\Sr;
use App\Models\SrSchedule;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
        Log::log('info', 'Running provider schedule');
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $srs = $this->srService->getServiceRequestRepository()->findSrsWithSchedule($provider);
            if ($srs->count() === 0) {
                continue;
            }
            $this->runBatchSrs($srs);
        }
    }

    private function runBatchSrs(Collection $srs, ?bool $isChild = false)
    {
        Log::log('info', 'Running batch schedule for SRs');
        foreach ($srs as $serviceRequest) {
            $this->runScheduleForSr($serviceRequest, $isChild);
        }
    }

    private function runScheduleForSr(Sr $sr, ?bool $isChild = false)
    {
        Log::log('info', 'Running schedule for SR: ' . $sr->label);
        $findParentChildSr = $this->srScheduleService->findScheduleForOperationBySr($sr);
        $isParentSrSchedule = $findParentChildSr['is_parent'];
        $schedule = $findParentChildSr['schedule'];

        if (!$schedule instanceof SrSchedule) {
            Log::log('info', 'No schedule found for SR: ' . $sr->label);
            return;
        }
        if (!$isChild) {
            if ($schedule->disabled && $schedule->disable_child_srs) {
                Log::log('info', 'Schedule is disabled for SR: ' . $sr->label);
                return;
            } elseif ($schedule->disabled && !$schedule->disable_child_srs) {
                Log::log('info', 'Schedule is disabled for SR: Running child srs' . $sr->label);
                $this->runChildSrSchedule($sr);
                return;
            }
        } else {
            if ($schedule->disabled) {
                return;
            }
        }
        if (!empty($schedule->start_date) && $schedule->start_date <= $this->today->toDateString()) {
            return;
        }
        if (!empty($schedule->end_date) && $schedule->end_date >= $this->today->toDateString()) {
            return;
        }

        if ($schedule->every_minute) {
            $this->getSrScheduleCall($sr, $schedule, 'everyMinute')->everyMinute();
            $this->runChildSrSchedule($sr);
        }
        if ($schedule->every_hour) {
            $minute = $schedule->minute;
            if (empty($minute)) {
                $minute = '00';
            }
            $scheduleCall = $this->getSrScheduleCall($sr, $schedule, "hourlyAt({$minute})");
            $scheduleCall->hourlyAt($minute);
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_day) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $this->getSrScheduleCall($sr, $schedule, "dailyAt({$hourMinutes})")
                ->dailyAt($hourMinutes);
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_weekday) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $weekday = $schedule->weekday;
            if (empty($weekday)) {
                $weekday = 1;
            }
            $this->getSrScheduleCall($sr, $schedule, "weeklyOn({$weekday} '{$hourMinutes}')")
                ->weeklyOn($weekday, $hourMinutes);
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_month) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $day = $schedule->day;
            if (empty($day)) {
                $day = 1;
            }
            $this->getSrScheduleCall($sr, $schedule, "monthlyOn({$day} '{$hourMinutes}')")
                ->monthlyOn($day, $hourMinutes);
            if (!$schedule->disable_child_srs) {
                $this->runChildSrSchedule($sr);
            }
        }

    }

    private function runChildSrSchedule(Sr $sr)
    {
        $childSrs = $sr->childSrs()->get();
        if ($childSrs->count() === 0) {
            return;
        }
        $this->runBatchSrs($childSrs, true);
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

    private function getSrScheduleCall(Sr $sr, SrSchedule $schedule, string $method)
    {
        return $this->schedule->call(
            function () use ($sr, $schedule, $method) {
                $this->providerEventsService->dispatchSrScheduleOperationEvent($sr, $schedule, $method);
            },
        );
    }

    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

}
