<?php

namespace App\Listeners;

use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\User;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RunSrOperationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private SrOperationsService $srOperationsService,
        private SrService $srService
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RunSrOperationEvent $event): void
    {
        Log::log('info', 'RunSsdsdsdsdrOperationListener');
        $srId = $event->srId;
        $userId = $event->userId;
        $queryData = $event->queryData;
        if (!is_int($userId)) {
            Log::log('error', 'RunSrOperationListener: $userId is not int');
            return;
        }
        if (!is_int($srId)) {
            Log::log('error', 'RunSrOperationListener: $srId is not int');
            return;
        }
        $user = $this->srService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'RunSrOperationListener: $user is not instance of User');
            return;
        }
        $sr = $this->srService->getServiceRequestRepository()->findById($srId);
        if (!$sr instanceof Sr) {
            Log::log('error', 'RunSrOperationListener: $sr is not instance of Sr');
            return;
        }
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            Log::log('error', 'RunSrOperationListener: $provider is not instance of Provider');
            return;
        }
        if (!is_array($queryData)) {
            Log::log('error', 'RunSrOperationListener: $queryData is not array');
            return;
        }
        $this->srOperationsService->setUser($user);
        $this->srOperationsService->getRequestOperation()->setProvider($provider);
        $this->srOperationsService->runOperationForSr($sr, $queryData);
    }
}
