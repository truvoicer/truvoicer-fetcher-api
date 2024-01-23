<?php

namespace App\Console;

use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\Task\ScheduleService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    private ScheduleService $scheduleService;
    public function __construct(Application $app, Dispatcher $events, ScheduleService $scheduleService)
    {
        parent::__construct($app, $events);
        $this->scheduleService = $scheduleService;
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
