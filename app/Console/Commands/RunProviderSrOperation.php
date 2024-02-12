<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Provider\ProviderEventService;
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
//        $type = $this->ask('Event type? (queue/event)');
//        $interval = $this->ask(
//            sprintf(
//                'Interval? (%s)',
//                implode(', ', array_keys(ScheduleService::SCHEDULE_INTERVALS))
//            )
//        );
//        $providerName = $this->ask('Enter provider name');
        $providerName = 'reed';
        $interval = 'every_minute';
        $type = 'event';
        $provider = Provider::where('name', '=', $providerName)->first();
        if (!$provider) {
            $this->error('Provider not found');
            return CommandAlias::FAILURE;
        }
        if ($type === 'queue') {
            $providerEventService->dispatchProviderSrOperationEvent($provider, $interval, true);
            return CommandAlias::SUCCESS;
        }
        $srOperationsService->runSrOperationsByInterval($provider, $interval, true);
        return CommandAlias::SUCCESS;
    }
}
