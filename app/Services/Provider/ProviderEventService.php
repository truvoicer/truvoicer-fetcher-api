<?php

namespace App\Services\Provider;

use App\Events\RunProviderSrOperationEvent;
use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Operations\SrOperationsService;

class ProviderEventService
{
    public function __construct() {
    }


    public function dispatchProviderSrOperationEvent(Provider $provider, string $interval)
    {
        return RunProviderSrOperationEvent::dispatch($provider, $interval);
    }
    public function dispatchSrOperationEvent(Sr $sr, ?array $queryData = ['query' => ''])
    {
        return RunSrOperationEvent::dispatch($sr, $queryData);
    }

}
