<?php

namespace App\Listeners;

use Truvoicer\TfDbReadCore\Events\RunProviderSrOperationEvent;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrOperationsService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use Truvoicer\TfDbReadCore\Services\Task\ScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $userId = $event->userId;
        $interval = $event->interval;
        $executeImmediately = $event->executeImmediately;
        if (!is_int($userId)) {
            Log::log('error', 'RunSrOperationListener: $userId is not int');
            return;
        }
        if (!is_int($providerId)) {
            Log::log('error', 'RunSrOperationListener: $providerId is not int');
            return;
        }
        $user = $this->providerService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'RunSrOperationListener: $user is not instance of User');
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
        $this->srOperationsService->setUser($user);
        $this->srOperationsService->runSrOperationsByInterval($provider, $interval, $executeImmediately);
    }
}
