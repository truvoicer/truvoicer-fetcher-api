<?php

namespace App\Services\Provider;

use App\Events\RunProviderSrOperationEvent;
use App\Events\RunSrOperationEvent;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Operations\SrOperationsService;
use Illuminate\Support\Facades\App;

class ProviderEventService
{
    public function __construct(
        private ProviderService $providerService
    ) {
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
        return RunProviderSrOperationEvent::dispatch($provider, $interval, $executeImmediately);
    }
    public function dispatchSrOperationEvent(Sr $sr, ?array $queryData = ['query' => ''])
    {
        return RunSrOperationEvent::dispatch($sr, $queryData);
    }

}
