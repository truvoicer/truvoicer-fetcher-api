<?php

namespace App\Services\Provider;

use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\SrSchedule;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Traits\User\UserTrait;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Provider\ProviderEventService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;

class ProviderScheduleService
{
    use UserTrait;

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

        $scheduleUserEmail = config('services.scheduler.schedule_user_email');

        if (empty($scheduleUserEmail)) {
            Log::log('info', 'No schedule user email found');
            return;
        }
        $this->providerService->getUserRepository()->addWhere('email', $scheduleUserEmail);
        $findUser = $this->providerService->getUserRepository()->findOne();
        if (!$findUser) {
            // dd(User::all()->toArray());
            Log::log('info', 'No schedule user found');
            return;
        }

        $this->setUser($findUser);

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
        foreach ($srs as $serviceRequest) {
            $this->runScheduleForSr($serviceRequest, $isChild);
        }
    }

    private function runScheduleForSr(Sr $sr, ?bool $isChild = false)
    {
        $findParentChildSr = $this->srScheduleService->findScheduleForOperationBySr($sr);
        $isParentSrSchedule = $findParentChildSr['is_parent'];
        $schedule = $findParentChildSr['schedule'];

        if (!$schedule instanceof SrSchedule) {
            Log::log('info', 'No schedule found for SR: ' . $sr->label);
            return;
        }
        if (!$isChild) {
            if ($schedule->disabled && $schedule->disable_child_srs) {
                return;
            } elseif ($schedule->disabled && !$schedule->disable_child_srs) {
                $this->runChildSrSchedule($sr);
                return;
            }
        } else {
            if ($schedule->disabled) {
                return;
            }
        }
        if (!empty($schedule->has_start_date) && !empty($schedule->start_date) && $schedule->start_date <= $this->today->toDateString()) {
            return;
        }
        if (!empty($schedule->has_end_date) && !empty($schedule->end_date) && $schedule->end_date >= $this->today->toDateString()) {
            return;
        }

        if ($schedule->every_minute) {
            $this->getSrScheduleCall($sr, $schedule, 'everyMinute')->everyMinute()->withoutOverlapping();
            $this->runChildSrSchedule($sr);
        }
        if ($schedule->every_hour) {
            $minute = $schedule->minute;

            if (empty($minute)) {
                $minute = '00';
            }
            $scheduleCall = $this->getSrScheduleCall($sr, $schedule, "hourlyAt({$minute})");
            $scheduleCall->hourlyAt($minute)->withoutOverlapping();
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_day) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $this->getSrScheduleCall($sr, $schedule, "dailyAt({$hourMinutes})")
                ->dailyAt($hourMinutes)->withoutOverlapping();
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_weekday) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $weekday = $schedule->weekday;
            if (empty($weekday)) {
                $weekday = 1;
            }
            $this->getSrScheduleCall($sr, $schedule, "weeklyOn({$weekday} '{$hourMinutes}')")
                ->weeklyOn($weekday, $hourMinutes)->withoutOverlapping();
            $this->runChildSrSchedule($sr);
        } else if ($schedule->every_month) {
            $hourMinutes = $this->getHourMinutes($schedule);
            $day = $schedule->day;
            if (empty($day)) {
                $day = 1;
            }
            $this->getSrScheduleCall($sr, $schedule, "monthlyOn({$day} '{$hourMinutes}')")
                ->monthlyOn($day, $hourMinutes)->withoutOverlapping();
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
                $this->providerEventsService->dispatchSrScheduleOperationEvent($this->user, $sr, $schedule, $method);
            },
        )->description('Sr operation schedules');
    }

    public function setSchedule(Schedule $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }

}
