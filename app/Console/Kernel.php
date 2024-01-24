<?php

namespace App\Console;

use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\Provider\ProviderEventsService;
use App\Services\Provider\ProviderService;
use App\Services\Task\ScheduleService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    private ProviderEventsService $providerService;
    public function __construct(Application $app, Dispatcher $events, ProviderEventsService $providerService)
    {
        parent::__construct($app, $events);
        $this->providerService = $providerService;
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        $this->providerService->providerSrSchedule($schedule);
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
