<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use Truvoicer\TruFetcherGet\Helpers\Operation\Request\OperationRequestBuilder;
use Truvoicer\TruFetcherGet\Models\S;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBQuery;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBRaw;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use MongoDB\Model\BSONDocument;
use stdClass;

class ApiRequestSearchService
{
    const RESERVED_SEARCH_RESPONSE_KEYS = [];
    private Collection $srResponseKeys;
    private MongoDBRaw $mongoDBRaw;
    private MongoDBQuery $mongoDbQuery;
    private string $type;
    protected array $notFoundProviders;
    protected array $itemSearchData;

    public function __construct(
        private Collection           $providers,
        private MongoDBRepository    $mongoDBRepository,
        private SrResponseKeyService $srResponseKeyService,
        private SrService            $srService,
        private S                    $service,
        protected OperationRequestBuilder $operationRequestBuilder,
    ) {}

    public function searchInit(): void
    {
        $collectionName = $this->mongoDBRepository->getCollectionNameByService(
            $this->service,
            $this->type
        );
        $this->mongoDBRaw = $this->mongoDBRepository->getMongoDBRaw();
        $this->mongoDBRaw->setCollection($collectionName);
        $this->mongoDbQuery = $this->mongoDBRepository->getMongoDBQuery();
        $this->mongoDbQuery->setCollection($collectionName);
    }

    private function prepareItemSearch(string $type, array $providers): void
    {
        $this->type = $type;
        $this->searchInit();

        foreach ($providers as $provider) {
            $providerName = $provider['provider_name'];
            $itemIds = $provider['ids'];

            $this->mongoDbQuery->addWhereGroup([
                [
                    'field' => 'item_id',
                    'compare' => 'in',
                    'value' => $itemIds,
                    'op' => 'and',
                ],
                [
                    'field' => 'provider',
                    'compare' => '=',
                    'value' => $providerName,
                    'op' => 'and',
                ],
            ]);
        }
    }

    public function runSingleItemSearch(string $type): BSONDocument|stdClass|null
    {
        $this->prepareItemSearch($type, $this->itemSearchData);

        return $this->mongoDbQuery->findOne();
    }

    private function getOrderByFromResponseKeys(Sr $sr): string|null
    {
        $orderBy = null;
        $srResponseKeys = $this->srResponseKeyService->findResponseKeysForOperationBySr($sr);
        $dateKeys = $srResponseKeys->filter(function ($srResponseKey) {
            return str_contains($srResponseKey->name, 'date');
        });
        if ($dateKeys->count() > 0) {
            $orderBy = $dateKeys->first()->name;
        }

        return $orderBy;
    }

    private function getOrderBy(Sr $sr, ?array $query): array
    {
        $sortOrder = 'desc';
        $defaultData = $sr->default_data;
        if (!is_array($query) || !empty($query['sort_by'])) {
            $orderBy = $query['sort_by'];
        } else if (!is_array($defaultData) || empty($defaultData['sort_by'])) {
            $orderBy = $this->getOrderByFromResponseKeys($sr);
        } else {
            $orderBy = $defaultData['sort_by'];
        }
        if (empty($orderBy)) {
            $orderBy = MongoDBRepository::CREATED_AT;
        }
        if (!empty($query['sort_order'])) {
            $sortOrder = $query['sort_order'];
        } else if (!empty($defaultData['sort_order'])) {
            $sortOrder = $defaultData['sort_order'];
        }

        return [$orderBy, $sortOrder];
    }

    private function getDatabaseFilter(array $queryData, string $key)
    {
        if (!array_key_exists('database_filters', $queryData)) {
            return false;
        }
        if (!is_array($queryData['database_filters'])) {
            return false;
        }
        if (!array_key_exists($key, $queryData['database_filters'])) {
            return false;
        }
        if (empty($queryData['database_filters'][$key]['operator'])) {
            return false;
        }
        return $queryData['database_filters'][$key]['operator'];
    }

    private function prepareQueryDataFilters(array $queryData)
    {
        $this->operationRequestBuilder
            ->setData($queryData)
            ->build();

        return $this->operationRequestBuilder->getProcessedFilters();
    }
    private function preparePriorityFields(string $searchQuery, array $queryData, array $srResponseKeyNames)
    {
        $priorityFields = [];

        if (
            !empty($queryData['search_fields']) &&
            is_array($queryData['search_fields'])
        ) {
            $priorityFields = $queryData['search_fields'];
        } else {
            $priorityFields = $srResponseKeyNames;
        }

        $priorityFieldData = array_map(
            fn($name) => ['column' => $name, 'value' => $searchQuery],
            $priorityFields
        );

        $filters = $this->prepareQueryDataFilters($queryData);
        foreach ($filters as $key => $queryItem) {
            if ($key === 'query' || $key == 'search_fields') {
                continue;
            }
            if (in_array($key, $srResponseKeyNames)) {
                continue;
            }
            $priorityFieldData = [
                ...$priorityFieldData,
                ...array_map(
                    fn($name) => ['column' => $name, 'value' => $queryItem],
                    $priorityFields
                )
            ];
        }

        return $priorityFieldData;
    }
    private function prepareDbQueryFilters(array $queryData, array $srResponseKeyNames)
    {
        $filters = $this->prepareQueryDataFilters($queryData);
        $queryFields = [];
        foreach ($filters as $key => $queryItem) {
            if ($key === 'query' || $key == 'search_fields') {
                continue;
            }

            if (!in_array($key, $srResponseKeyNames)) {
                continue;
            }
            if (!is_string($queryItem) && !is_numeric($queryItem)) {
                continue;
            }
            $queryFields[] = ['column' => $key, 'value' => $queryItem];
        }
        return $queryFields;
    }

    private function prepareSearchForSavedProviders(array $queryData)
    {
        $reservedKeys = array_column(
            DefaultData::getServiceResponseKeys(['xml', 'json']),
            SResponseKeysService::RESPONSE_KEY_NAME
        );
        $reservedKeys = array_merge($reservedKeys, self::RESERVED_SEARCH_RESPONSE_KEYS);
        $sort = [];
        $orderByData = [];

        if (!empty($queryData['sort_by'])) {
            $orderByData['sort_by'] = $queryData['sort_by'];
            unset($queryData['sort_by']);
        }
        if (!empty($queryData['sort_order'])) {
            $orderByData['sort_order'] = $queryData['sort_order'];
            unset($queryData['sort_order']);
        }
        $this->mongoDBRaw->setAggregation(true);

        $srResponseKeyNames = [];
        foreach ($this->providers as $index => $provider) {
            $srs = $this->srService->flattenSrCollection($this->type, $provider->sr);
            if ($srs->count() === 0) {
                continue;
            }

            foreach ($srs as $sr) {

                $excludeKeys = [
                    'sort_by',
                    'sort_order',
                    'page_size',
                    'page_number',
                    ...array_map(
                        fn($val) => $val[SResponseKeysService::RESPONSE_KEY_NAME],
                        array_merge(array_values(Arr::collapse(DataConstants::S_RESPONSE_KEY_GROUPS)))
                    )
                ];
                $srResponseKeys = $this->srResponseKeyService->findResponseKeysForOperationBySr(
                    $sr,
                    $excludeKeys,
                    ['searchable' => true]
                );
                $srResponseKeyNames = array_merge(
                    $srResponseKeyNames,
                    $srResponseKeys->pluck('name')->toArray()
                );
            }
        }


        $searchQuery = null;
        if (!empty($queryData['query'])) {
            $searchQuery = $queryData['query'];
        } elseif (
            !empty($queryData['filters']) &&
            is_array($queryData['filters'])
        ) {
            $queryFilter = collect($queryData['filters'])->firstWhere('field', 'query');
            $searchQuery = isset($queryFilter['value']) ? $queryFilter['value'] : null;
        }

        if (!empty($searchQuery)) {
            $this->mongoDBRaw->getMongoAggregationBuilder()->setPriorityFields(
                $this->preparePriorityFields(
                    $searchQuery,
                    $queryData,
                    $srResponseKeyNames
                )
            );
        }

        $queryFields = [];

        foreach ($queryData as $key => $queryItem) {
            if ($this->getDatabaseFilter($queryData, $key)) {
                $queryFields[] = [
                    'column' => $key,
                    'value' => $queryItem,
                    'comparison' => $queryData['database_filters'][$key]['operator'],
                    'operator' => 'and'
                ];
            }
        }

        $queryFields = $this->prepareDbQueryFilters(
            $queryData,
            $srResponseKeyNames
        );

        $first = true;
        foreach ($this->providers as $provider) {
            if (!$first) {
                $queryFields = [];
            }
            $srs = $this->srService->flattenSrCollection($this->type, $provider->sr);
            if ($srs->count() === 0) {
                $this->mongoDBRaw->getMongoAggregationBuilder()->addPrioritySearch(
                    [
                        ['column' => 'provider', 'value' => $provider->name, 'operator' => 'or'],
                        ...$queryFields
                    ],
                    'and'
                );
                $first = false;
                continue;
            }

            foreach ($srs as $sr) {
                list($orderBy, $sortOrder) = $this->getOrderBy($sr, $orderByData);
                if (!empty($orderBy) && in_array($sortOrder, $this->mongoDBRepository->getMongoDBQuery()::AVAILABLE_ORDER_DIRECTIONS)) {
                    $sort[] = [$orderBy, $sortOrder];
                }

                // Define the columns you want to search, in order of priority
                $this->mongoDBRaw->getMongoAggregationBuilder()->addPrioritySearch(
                    [
                        ['column' => 'provider', 'value' => $provider->name, 'operator' => 'or'],
                        ['column' => 'serviceRequest', 'value' => $sr->name, 'operator' => 'or'],
                        ...$queryFields
                    ],
                    'and'
                );
                $first = false;
            }
        }
        if (!empty($queryData['positions']) && is_array($queryData['positions'])) {
            $this->mongoDBRaw->setPositionConfig($queryData['positions']);
        }

        foreach ($sort as $sortData) {
            $this->mongoDBRaw->addSort($sortData[0], $sortData[1]);
        }
    }

    private function preparePagination(array $query): void
    {
        $this->mongoDBRaw->setPagination(true);
        if (!empty($query[DataConstants::PAGE_SIZE])) {
            $this->mongoDBRaw->setPerPage((int)$query[DataConstants::PAGE_SIZE]);
        }

        if (!empty($query[DataConstants::PAGE_NUMBER])) {
            $this->mongoDBRaw->setPage((int)$query[DataConstants::PAGE_NUMBER]);
        }
    }

    public function runListSearch(array $queryData): Collection|LengthAwarePaginator
    {
        if (!empty($this->itemSearchData) && count($this->itemSearchData)) {
            $this->prepareItemSearch($this->type, $this->itemSearchData);
            $this->preparePagination($queryData);
            $this->mongoDbQuery->setPagination(true);
            return $this->mongoDbQuery->findMany();
        }
        $this->searchInit();

        $this->preparePagination($queryData);
        $this->prepareSearchForSavedProviders(
            $queryData
        );
        return $this->mongoDBRaw->findMany();
    }

    public function setProviders(Collection $srs): void
    {
        $this->providers = $srs;
    }

    public function setService(S $service): void
    {
        $this->service = $service;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setNotFoundProviders(array $notFoundProviders): void
    {
        $this->notFoundProviders = $notFoundProviders;
    }

    public function setItemSearchData(array $itemSearchData): void
    {
        $this->itemSearchData = $itemSearchData;
    }
}
