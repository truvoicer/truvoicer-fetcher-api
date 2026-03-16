<?php

namespace App\Services\Provider;

use App\Events\RunProviderSrOperationEvent;
use App\Events\RunSrOperationEvent;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\SrSchedule;
use Truvoicer\TfDbReadCore\Models\User;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;

class ProviderEventService
{
    const LOGGING_NAME = 'ProviderEventService';

    const LOGGING_PATH = 'logs/provider_events_service/log.log';

    public function dispatchProviderSrOperationEvent(
        User $user,
        Provider $provider,
        string $interval,
        ?bool $executeImmediately = false
    ) {
        return RunProviderSrOperationEvent::dispatch(
            $user->id,
            $provider->id,
            $interval,
            $executeImmediately
        );
    }

    public function dispatchSrOperationEvent(
        User $user,
        Sr $sr,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    ) {
        return RunSrOperationEvent::dispatch(
            $user->id,
            $sr->id,
            $queryData
        );
    }

    public function dispatchSrScheduleOperationEvent(
        User $user,
        Sr $sr,
        SrSchedule $srSchedule,
        string $method,
        ?array $queryData = SrOperationsService::DEFAULT_QUERY_DATA,
    ) {
        return RunSrOperationEvent::dispatch($user->id, $sr->id, $queryData);
    }
}
