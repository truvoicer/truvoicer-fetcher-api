<?php

namespace App\Listeners;

use App\Events\ProcessSrOperationDataEvent;
use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessSrOperationDataListener
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
    public function handle(ProcessSrOperationDataEvent $event): void
    {
        Log::log('info', 'ProcessSrOperationDataListener');

        $srId = $event->srId;
        $userId = $event->userId;
        $queryData = $event->queryData;
        $apiResponse = $event->apiResponse;
        $runPagination = $event->runPagination;
        $runResponseKeySrRequests = $event->runResponseKeySrRequests;

        if (!is_int($userId)) {
            Log::log('error', 'ProcessSrOperationDataListener: $userId is not int');
            return;
        }
        if (!is_int($srId)) {
            Log::log('error', 'ProcessSrOperationDataListener: $srId is not int');
            return;
        }
        $user = $this->srService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'ProcessSrOperationDataListener: $user is not instance of User');
            return;
        }
        $sr = $this->srService->getServiceRequestRepository()->findById($srId);
        if (!$sr instanceof Sr) {
            Log::log('error', 'ProcessSrOperationDataListener: $sr is not instance of Sr');
            return;
        }
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            Log::log('error', 'ProcessSrOperationDataListener: $provider is not instance of Provider');
            return;
        }
        if (!is_array($queryData)) {
            Log::log('error', 'ProcessSrOperationDataListener: $queryData is not array');
            return;
        }
        $this->srOperationsService->setUser($user);
        $this->srOperationsService->getRequestOperation()->setProvider($provider);
        $this->srOperationsService->setRunPagination($runPagination);
        $this->srOperationsService->setRunResponseKeySrRequests($runResponseKeySrRequests);
        $saveData = $this->srOperationsService->processByType(
            $sr,
            SrResponseKeySrRepository::ACTION_STORE,
            $queryData,
            $apiResponse
        );
    }
}
