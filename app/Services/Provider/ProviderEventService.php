<?php

namespace App\Services\Provider;

use Truvoicer\TruFetcherGet\Events\RunProviderSrOperationEvent;
use Truvoicer\TruFetcherGet\Events\RunSrOperationEvent;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SrSchedule;
use App\Models\User;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
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

    public function dispatchProviderSrOperationEvent(
        User $user,
        Provider $provider,
        string $interval,
        ?bool $executeImmediately = false
    )
    {
        return RunProviderSrOperationEvent::dispatch(
            $user->id,
            $provider->id,
            $interval,
            $executeImmediately
        );
    }

    public function dispatchSrOperationEvent(
        User $user,
        Sr     $sr,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    )
    {
        return RunSrOperationEvent::dispatch(
            $user->id,
            $sr->id,
            $queryData
        );
    }
    public function dispatchSrScheduleOperationEvent(
        User $user,
        Sr     $sr,
        SrSchedule $srSchedule,
        string $method,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    )
    {
        return RunSrOperationEvent::dispatch($user->id, $sr->id, $queryData);
    }

}
