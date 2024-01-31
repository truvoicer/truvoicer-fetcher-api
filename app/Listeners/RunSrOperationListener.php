<?php

namespace App\Listeners;

use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Operations\SrOperationsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RunSrOperationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private SrOperationsService $srOperationsService
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
        $sr = $event->sr;
        $queryData = $event->queryData;
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
