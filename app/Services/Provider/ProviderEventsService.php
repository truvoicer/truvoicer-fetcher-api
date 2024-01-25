<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiManager\Response\Entity\RequestResponse;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Task\ScheduleService;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ProviderEventsService
{
    use ErrorTrait;

    const REQUIRED_FIELDS = [
        'item_id',
        'contentType',
        'provider',
        'requestService',
        'category',
    ];
    private MongoDBRepository $mongoDBRepository;
    private SrService $srService;
    private ProviderService $providerService;
    private RequestOperation $requestOperation;

    public function __construct(
        SrService        $srService,
        ProviderService  $providerService,
        RequestOperation $requestOperation,
    )
    {
        $this->srService = $srService;
        $this->providerService = $providerService;
        $this->requestOperation = $requestOperation;
        $this->mongoDBRepository = new MongoDBRepository();
    }

    public function providerSrSchedule(array $interval)
    {
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $this->runSrOperationsByInterval($provider, $interval['field']);
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

    public function runSrOperationsByInterval(Provider $provider, string $interval)
    {
        $srs = $this->getSrsByScheduleInterval($provider, $interval);
        if ($srs->count() === 0) {
            return;
        }
        $this->requestOperation->setProvider($provider);
        foreach ($srs as $serviceRequest) {
            $this->runOperationForSr($serviceRequest);
        }
    }

    private function buildSaveData(RequestResponse $requestResponse, array $data)
    {
        $requestData = $requestResponse->toArray();
        if (isset($requestData['requestData'])) {
            unset($requestData['requestData']);
        }
        $insertData = array_merge(
            $requestData,
            $data
        );

        return Arr::where($insertData, function ($value) {
            return (!is_array($value));
        });
    }

    private function validateRequiredFields(array $saveData)
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $saveData)) {
                return false;
            }
        }
        return true;
    }

    private function doesDataExistInDb(RequestResponse $requestResponse, array $saveData)
    {
        $findByData = array_map(function ($field) use ($saveData) {
            return [$field, $saveData[$field]];
        }, self::REQUIRED_FIELDS);
        $findExisting = $this->mongoDBRepository->findOneBy($findByData);
        if (!empty($findExisting)) {
            return true;
        }
        return false;
    }

    private function dataHasServiceRequest(array $data)
    {
//        if (!Arr::has($data, 'request_item')) {
//            return false;
//        }
//        $filtered = Arr::where($array, function (string|int $value, int $key) {
//            return is_string($value);
//        });
        return true;
    }

    public function runOperationForSr(Sr $sr)
    {
        $this->requestOperation->setSr($sr);
        $operationData = $this->requestOperation->runOperation(['query' => '']);
        if ($operationData->getStatus() !== 'success') {
            return false;
        }
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            return false;
        }
        $requestData = $operationData->getRequestData();

        $this->mongoDBRepository->setCollection($service->name);
        foreach ($requestData as $item) {
            $insertData = $this->buildSaveData($operationData, $item);
            if (!$insertData) {
                continue;
            }
            if (!$this->validateRequiredFields($insertData)) {
                continue;
            }
            if ($this->doesDataExistInDb($operationData, $insertData)) {
                continue;
            }
            if (!$this->mongoDBRepository->insert($insertData)) {
                $this->addError('error', 'Error inserting data into mongoDB');
            }
        }
    }
}
