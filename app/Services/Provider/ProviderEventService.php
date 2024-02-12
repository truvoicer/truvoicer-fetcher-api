<?php

namespace App\Services\Provider;

use App\Events\RunProviderSrOperationEvent;
use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Services\ApiManager\Operations\SrOperationsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ProviderEventService
{
    const LOGGING_NAME = 'ProviderEventService';
    const LOGGING_PATH = 'logs/provider_events_service/log.log';

    public function __construct(
        private ProviderService $providerService
    )
    {
    }


    public function dispatchProviderBatchSrOpEvent(string $interval)
    {
        $srOperationsService = App::make(SrOperationsService::class);
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $srs = $srOperationsService->getSrsByScheduleInterval($provider, $interval);
            if ($srs->count() === 0) {
                continue;
            }
            foreach ($srs as $serviceRequest) {
                $this->dispatchSrOperationEvent($serviceRequest);
            }
        }
    }

    public function dispatchProviderSrOperationEvent(Provider $provider, string $interval, ?bool $executeImmediately = false)
    {
        return RunProviderSrOperationEvent::dispatch($provider->id, $interval, $executeImmediately);
    }

    public function dispatchSrOperationEvent(
        Sr     $sr,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    )
    {
        return RunSrOperationEvent::dispatch($sr->id, $queryData);
    }
    public function dispatchSrScheduleOperationEvent(
        Sr     $sr,
        SrSchedule $srSchedule,
        string $method,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    )
    {
        Log::info(
            sprintf(
                'Dispatching SrOperationEvent for Sr %s | method: %s',
                $sr->label,
                $method
            ),
            $srSchedule->toArray()
        );
        return RunSrOperationEvent::dispatch($sr->id, $queryData);
    }

}
