<?php

namespace App\Listeners;

use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Operations\SrOperationsService;
use App\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
        Log::log('info', 'RunSrOperationListener');
        $srId = $event->srId;
        $queryData = $event->queryData;
        if (!is_int($srId)) {
            Log::log('error', 'RunSrOperationListener: $srId is not int');
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
        $this->srOperationsService->getRequestOperation()->setProvider($provider);
        $this->srOperationsService->runOperationForSr($sr, $queryData);
    }
}
