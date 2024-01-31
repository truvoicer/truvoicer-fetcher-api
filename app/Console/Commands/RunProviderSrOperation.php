<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\ApiManager\Operations\SrOperationsService;
use App\Services\Provider\ProviderEventService;
use App\Services\Task\ScheduleService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RunProviderSrOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:op:run';

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
    public function handle(SrOperationsService $srOperationsService, ProviderEventService $providerEventService)
    {
        $providerName = $this->ask('Enter provider name');
        $provider = Provider::where('name', '=', $providerName)->first();
        if (!$provider) {
            $this->error('Provider not found');
            return CommandAlias::FAILURE;
        }
//        $srOperationsService->runSrOperationsByInterval($provider, ScheduleService::SCHEDULE_EVERY_DAY);
        $providerEventService->dispatchProviderSrOperationEvent($provider, ScheduleService::SCHEDULE_EVERY_DAY);
        return CommandAlias::SUCCESS;
    }
}
