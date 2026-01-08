<?php

namespace App\Services\ApiServices\ServiceRequests;

use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\SrSchedule;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TfDbReadCore\Repositories\SrResponseKeySrRepository;
use Truvoicer\TfDbReadCore\Services\ApiManager\Operations\ApiRequestService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrOperationsService as ServiceRequestsSrOperationsService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use Truvoicer\TfDbReadCore\Services\Task\ScheduleService;

class SrOperationsService extends ServiceRequestsSrOperationsService
{
    public function __construct(
        protected SrService $srService,
        protected ProviderService $providerService,
        protected SrScheduleService $srScheduleService,
        protected ApiRequestService $requestOperation,
        protected MongoDBRepository $mongoDBRepository
    )
    {
        return parent::__construct(
            $srService,
            $providerService,
            $requestOperation,
            $mongoDBRepository
        );
    }
     public function getSrsByScheduleInterval(Provider $provider, string $interval, bool $executeImmediately = false)
    {
        if (!array_key_exists($interval, ScheduleService::SCHEDULE_INTERVALS)) {
            return false;
        }
        if (!in_array($interval, SrSchedule::FIELDS)) {
            return false;
        }
        return $this->srScheduleService->getSrScheduleRepository()->fetchSrsByScheduleInterval($provider, $interval, $executeImmediately);
    }

    public function runSrOperationsByInterval(Provider $provider, string $interval, ?bool $executeImmediately = false)
    {
        $srs = $this->getSrsByScheduleInterval($provider, $interval, $executeImmediately);
        if ($srs->count() === 0) {
            return;
        }
        $this->requestOperation->setProvider($provider);
        foreach ($srs as $serviceRequest) {
            $this->runOperationForSr($serviceRequest, SrResponseKeySrRepository::ACTION_STORE);
        }
    }

    public function providerSrSchedule(string $interval)
    {
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $this->runSrOperationsByInterval($provider, $interval);
        }
    }

}
