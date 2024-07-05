<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
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
        'serviceRequest',
        'service',
        'requestCategory',
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
    private ApiRequestService $requestOperation;
    private User $user;

    private int $offset = 0;
    private int $pageNumber = 1;
    private int $pageSize = 100;
    private int $totalItems = 1000;
    private int $totalPages = 1000;

    public function __construct(
        SrService         $srService,
        ProviderService   $providerService,
        ApiRequestService $requestOperation,
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
            $this->runOperationForSr($serviceRequest, SrResponseKeySrRepository::ACTION_STORE);
        }
    }

    private function buildSaveData(ApiResponse $requestResponse, array $data, array $queryData = [])
    {
        $requestData = $requestResponse->toArray();

        foreach (self::REMOVE_SAVE_DATA_FIELDS as $field) {
            if (isset($requestData[$field])) {
                unset($requestData[$field]);
            }
        }

        $data['query_params'] = $queryData;
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

    private function buildNestedSrResponseKeyData(array $responseKeyNames, string|int $value, array $data)
    {
        $buildData = array_filter($data, function ($key) use ($responseKeyNames) {
            return in_array($key, $responseKeyNames);
        }, ARRAY_FILTER_USE_KEY);
        $buildData['item_id'] = $value;
        return $buildData;
    }

    private function validateResponseKeySrConfig($data)
    {
        if (!is_array($data)) {
            return false;
        }
        return array_filter($data, function ($item) {
            if (!is_array($item)) {
                return false;
            }
            if (
                !Arr::exists($item, 'data') &&
                !Arr::exists($item, 'request_item')
            ) {
                return false;
            }
            if (!array_key_exists('request_item', $item)) {
                return false;
            }
            if (!is_array($item['request_item'])) {
                return false;
            }
//            if (empty($item['data'])) {
//                return false;
//            }

            $requestItem = $item['request_item'];
            if (empty($requestItem['request_name'])) {
                return false;
            }
            if (empty($requestItem['provider_name'])) {
                return false;
            }
            if (empty($requestItem['action'])) {
                return false;
            }
            return true;
        });
    }

    private function executeSrOperationRequest(Sr $sr, ?array $queryData = ['query' => ''])
    {
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return false;
        }

        if ($this->user->cannot('view', $provider)) {
            return false;
        }
        Log::channel(self::LOGGING_NAME)->info(
            sprintf(
                'Running operation for service request: %s | Request name: %s',
                $sr->label,
                $sr->name,
            )
        );
        $this->requestOperation->setSr($sr);
        Log::channel(self::LOGGING_NAME)->info(
            sprintf(
                'Found provider: %s | Service request: %s',
                $provider->label,
                $sr->name,
            )
        );
        $pageSizeResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];
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
        return $this->requestOperation->runOperation($queryData);
    }

    private function runResponseKeySrItem(Sr $parentSr, array $data)
    {

        foreach ($data as $key => $item) {
            $validate = $this->validateResponseKeySrConfig($item);
            if (!$validate) {
                continue;
            }
            foreach ($validate as $nested) {
                $requestItem = $nested['request_item'];
                $provider = $this->providerService->getUserProviderByName($this->user, $requestItem['provider_name']);
                if (!$provider instanceof Provider) {
                    continue;
                }
                $sr = SrRepository::getSrByName($provider, $requestItem['request_name']);
                if (!$sr instanceof Sr) {
                    continue;
                }

                $this->runOperationForSr(
                    $sr,
                    $requestItem['action'],
                    $this->buildNestedSrResponseKeyData(
                        $this->getRequestResponseKeyNames($requestItem),
                        $nested['data'],
                        $data
                    )
                );
            }
        }
        return true;
    }

    private function srOperationResponseHandler(Sr $sr, ApiResponse $apiResponse)
    {
        $provider = $sr->provider()->first();
        if ($apiResponse->getStatus() !== 'success') {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error running operation for service request: %s | Provider: %s | Error: %s',
                    $sr->label,
                    $provider->name,
                    $apiResponse->getMessage()
                ),
                $apiResponse->toArray()
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
                $apiResponse->toArray()
            );
            return false;
        }
        $requestData = $apiResponse->getRequestData();
        if (count($requestData) === 0) {
            $apiRequest = $apiResponse->getApiRequest();
            $response = $apiResponse->getResponse();
            Log::channel(self::LOGGING_NAME)->info(
                sprintf(
                    'No request data found for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
                [
                    'data' => $apiResponse->toArray(),
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
        return $apiResponse;
    }

    private function prepareDbSaveData(Sr $sr, ApiResponse $operationData, array $item, array $queryData = [])
    {
        $provider = $sr->provider()->first();
        $insertData = $this->buildSaveData($operationData, $item, $queryData);
        if (!$insertData) {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error building save data for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
            );
            return false;
        }
        if (!$this->validateRequiredFields($insertData)) {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error validating required fields for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
                $insertData
            );
            return false;
        }

        if ($this->doesDataExistInDb($operationData, $insertData)) {
            return false;
        }
        return $insertData;
    }

    private function saveToDb(Sr $sr, array $data)
    {
        $provider = $sr->provider()->first();
        if (!$this->mongoDBRepository->insert($data)) {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Error inserting data for service request: %s | Provider: %s',
                    $sr->label,
                    $provider->name,
                ),
                $data
            );
            return false;
        }
        return true;
    }

    private function validateAction(string $action)
    {
        return in_array($action, SrResponseKeySrRepository::ALLOWED_ACTIONS);
    }

    private function shouldSaveToDb(string $action)
    {
        return match ($action) {
            SrResponseKeySrRepository::ACTION_STORE => true,
            default => false,
        };
    }

    private function processRequestDataItem(Sr $sr, string $action, array $requestDataItem, array $queryData, ApiResponse $apiResponse)
    {

        if ($this->shouldSaveToDb($action)) {
            $item = $this->prepareDbSaveData($sr, $apiResponse, $requestDataItem, $queryData);
            if (!$item) {
                return false;
            }
        }

        $requestDataItem = $this->hasReturnValueResponseKeySrs($requestDataItem);

        if ($this->shouldSaveToDb($action)) {
            if (!$this->saveToDb($sr, $requestDataItem)) {
                return false;
            }
            $requestDataItem = $this->runResponseKeySrItem($sr, $requestDataItem);
            if (!$requestDataItem) {
                return false;
            }
        }
        return $requestDataItem;
    }

    private function processSingleSrData(Sr $sr, string $action, array $queryData, ApiResponse $apiResponse)
    {
        return $this->processRequestDataItem($sr, $action, $apiResponse->getRequestData(), $queryData, $apiResponse);
    }

    private function processListSrData(Sr $sr, string $action, array $queryData, ApiResponse $apiResponse)
    {
        $data = [];
        foreach ($apiResponse->getRequestData() as $item) {
            $requestDataItem = $this->processRequestDataItem($sr, $action, $item, $queryData, $apiResponse);
            if (!$requestDataItem) {
                continue;
            }
            $data[] = $requestDataItem;
        }
        if ($this->shouldSaveToDb($action)) {
            $this->runSrPagination($sr, $apiResponse);
        }

        return $data;
    }

    public function runOperationForSr(Sr $sr, string $action, ?array $queryData = ['query' => ''])
    {
        $provider = $sr->provider()->first();
        if (!$this->validateAction($action)) {
            Log::channel(self::LOGGING_NAME)->error(
                sprintf(
                    'Invalid action: %s for service request: %s, provider: %s',
                    $action,
                    $sr->label,
                    $provider->label,
                )
            );
            return false;
        }

        $apiResponse = $this->srOperationResponseHandler(
            $sr,
            $this->executeSrOperationRequest($sr, $queryData)
        );
        if (!$apiResponse) {
            return false;
        }

        if ($this->shouldSaveToDb($action)) {
            $collectionName = $this->mongoDBRepository->getCollectionName($sr);
            $this->mongoDBRepository->setCollection($collectionName);
        }

        return match ($sr->type) {
            'single' => $this->processSingleSrData($sr, $action, $queryData, $apiResponse),
            'list' => $this->processListSrData($sr, $action, $queryData, $apiResponse),
            default => false,
        };
    }

    private function getRequestResponseKeyNames(array $data)
    {
        if (
            !empty($data['request_response_keys']) &&
            is_array($data['request_response_keys'])
        ) {
            return $data['request_response_keys'];
        }
        return [];
    }

    private function getResponseResponseKeyNames(array $data)
    {
        if (
            !empty($data['response_response_keys']) &&
            is_array($data['response_response_keys'])
        ) {
            return $data['response_response_keys'];
        }
        return [];
    }

    private function buildReturnValue(Sr $sr, array $data, array $responseKeyNames)
    {
        if (!count($responseKeyNames)) {
            return null;
        }
        switch ($sr->type) {
            case 'single':
                if (count($responseKeyNames) === 1) {
                    return $data[$responseKeyNames[0]];
                }
                return array_filter($data, function ($key) use ($responseKeyNames) {
                    return in_array($key, $responseKeyNames);
                }, ARRAY_FILTER_USE_KEY);

            case 'list':
                return array_map(function ($item) use ($responseKeyNames) {
                    if (count($responseKeyNames) === 1) {
                        return $item[$responseKeyNames[0]];
                    }
                    return array_filter($item, function ($key) use ($responseKeyNames) {
                        return in_array($key, $responseKeyNames);
                    }, ARRAY_FILTER_USE_KEY);
                }, $data);
            default:
                return null;
        }
    }

    private function hasReturnValueResponseKeySrs(array $data)
    {
        foreach ($data as $keyName => $item) {
            $validate = $this->validateResponseKeySrConfig($item);
            if (!$validate) {
                continue;
            }
            $filtered = array_filter($validate, function ($nested) {
                $requestItem = $nested['request_item'];
                return $requestItem['action'] === 'return_value';
            }, ARRAY_FILTER_USE_BOTH);#
            if (count($filtered) === 0) {
                continue;
            }
            $filtered = array_map(function ($nested) use ($data) {
                $requestItem = $nested['request_item'];
                $singleRequest = $requestItem['single_request'] ?? false;

                $provider = $this->providerService->getUserProviderByName($this->user, $requestItem['provider_name']);
                if (!$provider instanceof Provider) {
                    return false;
                }
                $sr = SrRepository::getSrByName($provider, $requestItem['request_name']);
                if (!$sr instanceof Sr) {
                    return false;
                }

                $responseKeyNames = $this->getResponseResponseKeyNames($requestItem);
                $srConfigData = $nested['data'];

                $returnValue = null;
                if (is_array($srConfigData)) {
                    $returnValue = [];
                    $step = 0;
                    foreach ($srConfigData as $key => $value) {
                        if (!is_string($value) && !is_numeric($value)) {
                            $step++;
                            continue;
                        }

                        $queryData = $this->buildNestedSrResponseKeyData(
                            $this->getRequestResponseKeyNames($requestItem),
                            $value,
                            $data
                        );

                        $response = $this->runOperationForSr(
                            $sr,
                            $requestItem['action'],
                            $queryData
                        );
                        if (!$response) {
                            $step++;
                            continue;
                        }

                        $returnValue = array_merge(
                            $returnValue,
                            $this->buildReturnValue(
                                $sr,
                                $response,
                                $responseKeyNames
                            )
                        );

                        if ($singleRequest) {
                            break;
                        }
                    }

                } elseif (is_string($srConfigData)) {
                    $queryData = $this->buildNestedSrResponseKeyData(
                        $this->getRequestResponseKeyNames($requestItem),
                        $srConfigData,
                        $data
                    );
                    $response = $this->runOperationForSr(
                        $sr,
                        $requestItem['action'],
                        $queryData
                    );
                    if (!$response) {
                        return false;
                    }
                    $returnValue = $this->buildReturnValue(
                        $sr,
                        $response,
                        $responseKeyNames
                    );
                }
                return $returnValue;
            }, $filtered);
            if (count($filtered) === 1) {
                $data[$keyName] = $filtered[array_key_first($filtered)];
                continue;
            }
            $data[$keyName] = $filtered;
        }
        return $data;
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

    private function getTotalItems(ApiResponse $apiResponse)
    {
        $totalItemsResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME];
        $extraData = $apiResponse->getExtraData();
        if (!isset($extraData[$totalItemsResponseKey]) || $extraData[$totalItemsResponseKey] === '') {
            return $this->totalItems;
        }
        return (int)$extraData[$totalItemsResponseKey];
    }

    private function getPageSize(ApiResponse $apiResponse)
    {
        $pageSizeResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];
        $extraData = $apiResponse->getExtraData();
        if (!isset($extraData[$pageSizeResponseKey]) || $extraData[$pageSizeResponseKey] === '') {
            return false;
        }
        return (int)$extraData[$pageSizeResponseKey];
    }

    private function runSrPaginationOffset(Sr $sr, ApiResponse $apiResponse)
    {
        Log::channel(self::LOGGING_NAME)->info('Running offset pagination!');
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }

        $extraData = $apiResponse->getExtraData();

        $this->totalItems = $this->getTotalItems($apiResponse);

        $pageSize = $this->getPageSize($apiResponse);

        $offsetResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME];
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
        $this->runOperationForSr($sr, SrResponseKeySrRepository::ACTION_STORE,
            [
                'query' => '',
                DataConstants::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->offset,
            ]);
    }

    private function getTotalPagesFromTotalItems()
    {
        return $this->totalItems / $this->pageSize;
    }

    private function getPageFromOffset(int $offset)
    {
        $totalPages = $this->getTotalPagesFromTotalItems();
        $offsetPageCount = $this->totalItems - $offset;
        return round($totalPages - round($offsetPageCount / $this->pageSize));
    }

    private function getOffsetFromPageNumber()
    {
        return ($this->pageNumber * $this->pageSize);
    }

    private function runSrPaginationPage(Sr $sr, ApiResponse $apiResponse)
    {
        Log::channel(self::LOGGING_NAME)->info('Running page pagination!');
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return;
        }

        $totalItemsResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME];
        $totalPagesResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['TOTAL_PAGES'][SResponseKeysService::RESPONSE_KEY_NAME];
        $pageNumberResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME];
        $pageSizeResponseKey = DataConstants::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][SResponseKeysService::RESPONSE_KEY_NAME];

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
        $this->runOperationForSr($sr,
            SrResponseKeySrRepository::ACTION_STORE,
            [
                'query' => '',
                DataConstants::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME] => $this->pageNumber,
            ]);
    }

    public function getRequestOperation(): ApiRequestService
    {
        return $this->requestOperation;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
