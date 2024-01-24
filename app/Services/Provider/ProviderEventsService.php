<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\SrSchedule;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Task\ScheduleService;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ProviderEventsService
{
    use ErrorTrait;
    private MongoDBRepository $mongoDBRepository;
    private SrService $srService;
    private ProviderService $providerService;
    private RequestOperation $requestOperation;
    public function __construct(
        SrService $srService,
        ProviderService $providerService,
        RequestOperation $requestOperation,
    ) {
        $this->srService = $srService;
        $this->providerService = $providerService;
        $this->requestOperation = $requestOperation;
        $this->mongoDBRepository = new MongoDBRepository();
    }

    public function providerSrSchedule(array $interval)
    {
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $srs = $this->getSrsByScheduleInterval($provider, $interval['field']);
            if ($srs->count() === 0) {
                return;
            }
            $this->runSrOperations($srs, $provider);
        }
    }

    public function getSrsByScheduleInterval(Provider $provider, string $interval)
    {
        if (!array_key_exists($interval, ScheduleService::SCHEDULE_INTERVALS)) {
            return false;
        }
        if (!in_array($interval, SrSchedule::FIELDS)) {
            return false;
        }
        return $this->srService->getSrScheduleRepository()->fetchSrsByScheduleInterval($provider, $interval);
    }
    public function runSrOperations(Collection $serviceRequests, Provider $provider)
    {
        $this->requestOperation->setProvider($provider);
        $user = $provider->providerUser()->first()->user()->first();
        $this->requestOperation->setUser($user);
        foreach ($serviceRequests as $serviceRequest) {
            $this->requestOperation->setSr($serviceRequest);
            $operationData = $this->requestOperation->runOperation(['query' => '']);
            if ($operationData->getStatus() !== 'success') {
                continue;
            }
            $toArray = $operationData->toArray();
            $requestData = Arr::pull($toArray, 'requestData');

            $this->mongoDBRepository->setCollection($serviceRequest->s()->first()->name);
            foreach ($requestData as $item) {
                $insertData = array_merge(
                    $toArray,
                    $item
                );
                $findExisting = $this->mongoDBRepository->findOneBy([
                    ['item_id', $insertData['item_id']],
                    ['contentType', $insertData['contentType']],
                    ['provider',  $insertData['provider']],
                    ['requestService', $insertData['requestService']],
                    ['category', $insertData['category']],
                ]);
                if ($findExisting) {
                    continue;
                }
                if (!$this->mongoDBRepository->insert($insertData)) {
                    $this->addError('error', 'Error inserting data into mongoDB');
                }
            }
        }
    }
}
