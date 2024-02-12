<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Provider\ProviderEventService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RunSrOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sr:op:run';

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
        $type = $this->ask('Event type? (queue/event)');
        $providerName = $this->ask('Enter provider name');
        $srName = $this->ask('Enter service request name');
        $provider = Provider::where('name', '=', $providerName)->first();
        if (!$provider) {
            $this->error('Provider not found');
            return CommandAlias::FAILURE;
        }
        $sr = $provider->sr()->where('name', '=', $srName)->first();
        if (!$sr) {
            $this->error('Sr not found');
            return CommandAlias::FAILURE;
        }

        if ($type === 'queue') {
            $providerEventService->dispatchSrOperationEvent($sr);
            return CommandAlias::SUCCESS;
        }

        $srOperationsService->getRequestOperation()->setProvider($provider);
        $srOperationsService->runOperationForSr($sr);
        return CommandAlias::SUCCESS;
    }
}
