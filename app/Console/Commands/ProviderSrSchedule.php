<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\ApiManager\Operations\SrOperationsService;
use App\Services\Task\ScheduleService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProviderSrSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run provider sr schedules';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SrOperationsService $providerService)
    {
        $provider = Provider::where('name', '=', 'reed')->first();
        $providerService->runSrOperationsByInterval($provider, ScheduleService::SCHEDULE_EVERY_DAY);
        return CommandAlias::SUCCESS;
    }
}
