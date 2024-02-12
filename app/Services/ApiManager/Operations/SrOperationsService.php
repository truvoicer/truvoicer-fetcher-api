<?php

namespace App\Services\ApiManager\Operations;

use App\Library\Defaults\DefaultData;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Provider\ProviderService;
use App\Services\Task\ScheduleService;
use App\Traits\Error\ErrorTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SrOperationsService
{
    use ErrorTrait;
    const LOGGING_NAME = 'SrMongoOperations';
    const LOGGING_PATH = 'logs/sr_mongo_operations/log.log';

    const REQUIRED_FIELDS = [
        'item_id',
        'contentType',
        'provider',
        'requestService',
        'category',
    ];
    const REMOVE_SAVE_DATA_FIELDS = [
        'requestData',
        'apiRequest',
        'response',
    ];
    public const DEFAULT_QUERY_DATA = [
        'query' => '',
    ];
    private MongoDBRepository $mongoDBRepository;
    private SrService $srService;
    private ProviderService $providerService;
    private SrScheduleService $srScheduleService;
    private RequestOperation $requestOperation;
    private int $offset = 0;
    private int $pageNumber = 1;
    private int $pageSize = 100;
    private int $totalItems = 1000;
    private int $totalPages = 1000;

    public function __construct(
        SrService        $srService,
        ProviderService  $providerService,
        RequestOperation $requestOperation,
        SrScheduleService $srScheduleService
    )
    {
        $this->srService = $srService;
        $this->providerService = $providerService;
        $this->requestOperation = $requestOperation;
        $this->srScheduleService = $srScheduleService;
        $this->mongoDBRepository = new MongoDBRepository();
    }

    public function providerSrSchedule(string $interval)
    {
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $this->runSrOperationsByInterval($provider, $interval);
        }
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
            $this->runOperationForSr($serviceRequest);
        }
    }

    private function buildSaveData(ApiResponse $requestResponse, array $data)
    {
        $requestData = $requestResponse->toArray();
        foreach (self::REMOVE_SAVE_DATA_FIELDS as $field) {
            if (isset($requestData[$field])) {
                unset($requestData[$field]);
            }
        }
        $insertData = array_merge(
            $requestData,
            $data
        );
        return $insertData;
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

    private function doesDataExistInDb(ApiResponse $requestResponse, array $saveData)
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

    private function runResponseKeySrItem(Sr $parentSr, array $data)
    {
        $provider = $parentSr->provider()->first();
        if (!$provider instanceof Provider) {
            return false;
        }
        foreach ($data as $key => $item) {

            if (!is_array($item)) {
                continue;
            }
            foreach ($item as $nested) {
                if (!is_array($nested)) {
                    continue;
                }
                if (
                    !Arr::exists($nested, 'data') &&
                    !Arr::exists($nested, 'request_item')
                ) {
                    continue;
                }
                if (!array_key_exists('request_item', $nested)) {
                    continue;
                }
                if (!is_array($nested['request_item'])) {
                    continue;
                }
                if (empty($nested['data'])) {
                    continue;
                }

                $requestItem = $nested['request_item'];
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
                        'query' => $nested['data'],
                    ]
                );
            }
        }
        return true;
    }

    private function getCollectionName(Sr $sr)
    {
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            return false;
        }
        return sprintf(
            '%s_%s',
            $service->name,
            $sr->name
        );
    }
    public function runOperationForSr(Sr $sr, ?array $queryData = ['query' => ''])
    {
        Log::channel(self::LOGGING_NAME)->info(
            sprintf(
                'Running operation for service request: %s | Request name: %s',
                $sr->label,
                $sr->name,
            )
        );
        $this->requestOperation->setSr($sr);
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }
        Log::channel(self::LOGGING_NAME)->info(
            sprintf(
                'Found provider: %s | Service request: %s',
                $provider->label,
                $sr->name,
            )
        );
        $pageSizeResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];
        $pageSize = SrResponseKeyRepository::getSrResponseKeyValueByName(
            $provider,
            $sr,
            $pageSizeResponseKey
        );
        if (!empty($pageSize)) {
            $queryData[$pageSizeResponseKey] = (int)$pageSize;
        } else {
            $queryData[$pageSizeResponseKey] = $this->pageSize;
        }
        $operationData = $this->requestOperation->runOperation($queryData);

        if ($operationData->getStatus() !== 'success') {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error running operation for service request: %s | Provider: %s | Error: %s',
                    $sr->label,
                    $provider->name,
                    $operationData->getMessage()
                ),
                $operationData->toArray()
            );
            return false;
        }
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error finding service for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
                $operationData->toArray()
            );
            return false;
        }
        $requestData = $operationData->getRequestData();
        if (count($requestData) === 0) {
            $apiRequest = $operationData->getApiRequest();
            $response = $operationData->getResponse();
            Log::channel(self::LOGGING_NAME)->info(
                sprintf(
                    'No request data found for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
                [
                    'data' => $operationData->toArray(),
                    'request_data' => ($apiRequest) ? $apiRequest->toArray() : [],
                    'response' => (empty($response)) ? null : [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                    ]
                ]
            );
            return false;
        }
        foreach ($requestData as $item) {
            $collectionName = $this->getCollectionName($sr);
            $this->mongoDBRepository->setCollection($collectionName);
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
            $insertData = $this->runResponseKeySrItem($sr, $item);
            if (!$insertData) {
                continue;
            }
        }
        $this->runSrPagination($sr, $operationData);

        return true;
    }

    private function runSrPagination(Sr $sr, ApiResponse $apiResponse)
    {
        Log::channel(self::LOGGING_NAME)->info('Starting pagination!');
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }
        $paginationType = $sr->pagination_type;
        if (empty($paginationType)) {
            return;
        }
        if (!is_array($sr->pagination_type) || empty($sr->pagination_type['value'])) {
            return;
        }
        $paginationType = $sr->pagination_type['value'];

        Log::channel(self::LOGGING_NAME)->info('Pagination type: ' . $paginationType);
        switch ($paginationType) {
            case 'offset':
                $this->runSrPaginationOffset($sr, $apiResponse);
                break;
            case 'page':
                $this->runSrPaginationPage($sr, $apiResponse);
                break;
        }
    }

    private function getTotalItems(ApiResponse $apiResponse) {
        $totalItemsResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME];
        $extraData = $apiResponse->getExtraData();
        if (!isset($extraData[$totalItemsResponseKey]) || $extraData[$totalItemsResponseKey] === '') {
            return $this->totalItems;
        }
        return (int)$extraData[$totalItemsResponseKey];
    }
    private function getPageSize(ApiResponse $apiResponse) {
        $pageSizeResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];
        $extraData = $apiResponse->getExtraData();
        if (!isset($extraData[$pageSizeResponseKey]) || $extraData[$pageSizeResponseKey] === '') {
            return false;
        }
        return (int)$extraData[$pageSizeResponseKey];
    }
    private function runSrPaginationOffset(Sr $sr, ApiResponse $apiResponse) {
        Log::channel(self::LOGGING_NAME)->info('Running offset pagination!');
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }

        $extraData = $apiResponse->getExtraData();

        $this->totalItems = $this->getTotalItems($apiResponse);

        $pageSize  = $this->getPageSize($apiResponse);

        $offsetResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME];
        if (isset($extraData[$offsetResponseKey]) && $extraData[$offsetResponseKey] !== '') {
            $this->offset = (int)$extraData[$offsetResponseKey];
        } else {
            $this->offset += $pageSize;
        }

        if ($pageSize !== false) {
            $this->pageSize = $pageSize;
        }
        Log::channel(self::LOGGING_NAME)->info(
            sprintf(
                'Total items: %s | Page size: %s | Offset: %s',
                $this->totalItems,
                $this->pageSize,
                $this->offset
            )
        );

        if ($this->offset >= $this->totalItems) {
            Log::channel(self::LOGGING_NAME)->info(
                sprintf(
                    'Offset: %s is greater than or equal to total items: %s | Page size: %s',
                    $this->totalItems,
                    $this->pageSize,
                    $this->offset
                )
            );
            return;
        }
        $this->runOperationForSr($sr, [
            'query' => '',
            DefaultData::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->offset,
        ]);
    }

    private function getTotalPagesFromTotalItems() {
        return $this->totalItems / $this->pageSize;
    }
    private function getPageFromOffset(int $offset) {
        $totalPages = $this->getTotalPagesFromTotalItems();
        $offsetPageCount = $this->totalItems - $offset;
        return round($totalPages - round($offsetPageCount / $this->pageSize));
    }
    private function getOffsetFromPageNumber() {
        return ($this->pageNumber * $this->pageSize);
    }
    private function runSrPaginationPage(Sr $sr, ApiResponse $apiResponse) {
        Log::channel(self::LOGGING_NAME)->info('Running page pagination!');
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }

        $totalItemsResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME];
        $totalPagesResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_PAGES'][SResponseKeysService::RESPONSE_KEY_NAME];
        $pageNumberResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME];
        $pageSizeResponseKey = DefaultData::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];

        $extraData = $apiResponse->getExtraData();
//        $totalItems = null;
        $totalPages = null;
//        if (isset($extraData[$totalItemsResponseKey]) && $extraData[$totalItemsResponseKey] !== '') {
//            $totalItems = (int)$extraData[$totalItemsResponseKey];
//        }
        if (!isset($extraData[$totalPagesResponseKey]) || $extraData[$totalPagesResponseKey] === '') {
            return;
        }
        $totalPages = (int)$extraData[$totalPagesResponseKey];

        if (isset($extraData[$pageNumberResponseKey]) && $extraData[$pageNumberResponseKey] !== '') {
            $this->pageNumber = (int)$extraData[$pageNumberResponseKey];
        }
        if (isset($extraData[$pageSizeResponseKey]) && $extraData[$totalItemsResponseKey] !== '') {
            $this->pageSize = (int)$extraData[$pageSizeResponseKey];
        }
        $this->totalItems = 5;

        $this->pageNumber += 1;
        if ($this->pageNumber > $totalPages) {
            return;
        }
        $this->runOperationForSr($sr, [
            'query' => '',
            DefaultData::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->pageNumber,
        ]);
    }

    public function getRequestOperation(): RequestOperation
    {
        return $this->requestOperation;
    }
}
