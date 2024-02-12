<?php

namespace App\Listeners;

use App\Events\RunProviderSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Operations\SrOperationsService;
use App\Services\Provider\ProviderService;
use App\Services\Task\ScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RunProviderSrOperationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private SrOperationsService $srOperationsService,
        private ProviderService $providerService,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RunProviderSrOperationEvent $event): void
    {
        Log::log('info', 'RunSrOperationListener');
        $providerId = $event->providerId;
        $interval = $event->interval;
        $executeImmediately = $event->executeImmediately;
        if (!is_int($providerId)) {
            Log::log('error', 'RunSrOperationListener: $providerId is not int');
            return;
        }
        $provider = $this->providerService->getProviderRepository()->findById($providerId);
        if (!$provider instanceof Provider) {
            Log::log('error', 'RunSrOperationListener: $provider is not instance of Provider');
            return;
        }
        if (!is_string($interval) || !array_key_exists($interval, ScheduleService::SCHEDULE_INTERVALS)) {
            Log::log('error', 'RunSrOperationListener: $interval is not string');
            return;
        }
        $this->srOperationsService->runSrOperationsByInterval($provider, $interval, $executeImmediately);
    }
}
