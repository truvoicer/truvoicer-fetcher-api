<?php

namespace App\Services\Provider;

use App\Library\Defaults\DefaultData;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrSchedule;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiManager\Response\Entity\RequestResponse;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
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
    private int $offset = 0;
    private int $pageNumber = 1;
    private int $pageSize = 100;

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
        return array_map(function ($item) {
            if (is_array($item) && isset($item['data'])) {
                return $item['data'];
            }
            return null;
        }, $insertData);
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

    private function runNestedServiceRequests(Sr $parentSr, array $data)
    {
        $provider = $parentSr->provider()->first();
        if (!$provider instanceof Provider) {
            return false;
        }
        foreach ($data as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!array_key_exists('request_item', $item)) {
                continue;
            }
            if (!is_array($item['request_item'])) {
                continue;
            }
            if (empty($item['data'])) {
                continue;
            }

            $requestItem = $item['request_item'];
            if (empty($requestItem['request_name'])) {
                continue;
            }
            if (empty($requestItem['request_operation'])) {
                continue;
            }
            $sr = SrRepository::getSrByName($provider, $requestItem['request_operation']);
            if (!$sr instanceof Sr) {
                continue;
            }
            $this->runOperationForSr(
                $sr,
                [
                    'query' => $requestItem['data'],
                ]
            );
        }
        return true;
    }

    public function runOperationForSr(Sr $sr, ?array $requestData = ['query' => ''])
    {
        $this->requestOperation->setSr($sr);
        $operationData = $this->requestOperation->runOperation($requestData);
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
            $insertData = $this->runNestedServiceRequests($sr, $item);
            if (!$insertData) {
                continue;
            }
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
            $this->runSrPagination($sr);
        }
    }

    private function runSrPagination(Sr $sr)
    {
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }
        $paginationType = $sr->pagination_type;
        if (empty($paginationType)) {
            return;
        }
        switch ($paginationType) {
            case 'offset':
                $this->runSrPaginationOffset($sr);
                break;
            case 'page':
                $this->runSrPaginationPage($sr);
                break;
        }
    }

    private function runSrPaginationOffset(Sr $sr) {
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }
        $totalItemsResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME]
        );
        $offsetResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME]
        );
        $pageSizeResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME]
        );
        if (!empty($pageSizeResponseKey) && is_integer($pageSizeResponseKey)) {
            $this->pageSize = $pageSizeResponseKey;
        }

        if (empty($totalItemsResponseKey)) {
            return;
        }
        if (!empty($offsetResponseKey) && is_integer($offsetResponseKey)) {
            $this->offset = $offsetResponseKey;
        }

        $this->offset += $this->pageSize;
        $totalItems = $totalItemsResponseKey->srResponseKey()->first()->value;
        if ($this->offset >= $totalItems) {
            return;
        }
        $this->runOperationForSr($sr, [
            'query' => '',
            DefaultData::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->offset,
        ]);
    }
    private function runSrPaginationPage(Sr $sr) {
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }

        $totalItemsResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME]
        );
        $pageSizeResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME]
        );
        $pageNumberResponseKey = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            DefaultData::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME]
        );

        if (empty($totalItemsResponseKey)) {
            return;
        }
        if (!empty($pageSizeResponseKey) && is_integer($pageSizeResponseKey)) {
            $this->pageSize = $pageSizeResponseKey;
        }
        if (!empty($pageNumberResponseKey) && is_integer($pageNumberResponseKey)) {
            $this->pageNumber = $pageNumberResponseKey;
        }

        $this->pageNumber += 1;
        $this->runOperationForSr($sr, [
            'query' => '',
            DefaultData::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->pageNumber,
        ]);
    }
}
