<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\S;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRaw;
use App\Repositories\MongoDB\MongoDBRepository;
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

class ApiRequestSearchService
{
    const RESERVED_SEARCH_RESPONSE_KEYS = [

    ];
    private Collection $srResponseKeys;
    private MongoDBRaw $mongoDBRaw;
    private string $type;
    protected array $notFoundProviders;
    protected array $itemSearchData;

    public function __construct(
        private Collection           $providers,
        private MongoDBRepository    $mongoDBRepository,
        private SrResponseKeyService $srResponseKeyService,
        private SrService            $srService,
        private S                    $service,
    ) {}

    public function searchInit(): void
    {
        $collectionName = $this->mongoDBRepository->getCollectionNameByService(
            $this->service,
            $this->type
        );
        $this->mongoDBRaw = $this->mongoDBRepository->getMongoDBRaw();
        $this->mongoDBRaw->setCollection($collectionName);
    }

    private function prepareItemSearch(string $type, array $providers): void
    {
        $this->type = $type;
        $this->searchInit();

        foreach ($providers as $provider) {
            $providerName = $provider['provider_name'];
            $itemIds = $provider['ids'];

            $this->mongoDBRaw->addWhereGroup(
                'and',
                function ($query) use ($itemIds) {
                    $query->addWhere('item_id', 'in', $itemIds, 'and');
                    foreach ($itemIds as $key => $value) {
                        if (!is_numeric($value)) {
                            continue;
                        }
                        $query->addWhere('item_id', intval($value), 'or');
                    }
                }
            );
            $this->mongoDBRaw->addWhereGroup(
                'and',
                function ($query) use ($providerName) {
                    $query->addWhere('provider', '=', $providerName, 'and');
                }
            );
        }
    }

    public function runSingleItemSearch(string $type): BSONDocument|null
    {
        $this->prepareItemSearch($type, $this->itemSearchData);
        return $this->mongoDBRaw->findOne();
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
        foreach ($this->providers as $provider) {
            $srs = $this->srService->flattenSrCollection($this->type, $provider->sr);
            if ($srs->count() === 0) {
                continue;
            }

            foreach ($srs as $sr) {
                list($orderBy, $sortOrder) = $this->getOrderBy($sr, $orderByData);
                if (!empty($orderBy) && in_array($sortOrder, $this->mongoDBRepository->getMongoDBQuery()::AVAILABLE_ORDER_DIRECTIONS)) {
                    $sort[] = [$orderBy, $sortOrder];
                }

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
                $srResponseKeyNames = $srResponseKeys->pluck('name')->toArray();

                $searchQuery = null;
                if (!empty($queryData['query'])) {
                    $searchQuery = $queryData['query'];
                }

                if (
                    !empty($queryData['search_fields']) &&
                    is_array($queryData['search_fields'])
                ) {
                    $priorityFields = array_map(
                        fn($name) => ['column' => $name, 'value' => $searchQuery],
                        $queryData['search_fields']
                    );
                } else {
                    $priorityFields = array_map(
                        fn($name) => ['column' => $name, 'value' => $searchQuery],
                        $srResponseKeyNames
                    );
                }

                $queryFields = [];

                foreach ($queryData as $key => $queryItem) {
                    if ($key === 'query' || $key == 'search_fields') {
                        continue;
                    }
                    if (!in_array($key, $srResponseKeyNames)) {
                        continue;
                    }
                    if (!is_string($queryItem) && !is_numeric($queryItem)) {
                        continue;
                    }
                    if ($this->getDatabaseFilter($queryData, $key)) {
                        $queryFields[] = [
                            'column' => $key,
                            'value' => $queryItem,
                            'operator' => $queryData['database_filters'][$key]['operator']
                        ];
                    } else {
                        $queryFields[] = ['column' => $key, 'value' => $queryItem];
                    }
                }

                // Define the columns you want to search, in order of priority
                $this->mongoDBRaw->getMongoAggregationBuilder()->addPrioritySearch(
                    $priorityFields,
                    [
                        ['column' => 'provider', 'value' => $provider->name],
                        ['column' => 'serviceRequest', 'value' => $sr->name],
                        ...$queryFields
                    ],
                    'or'
                );
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
            return $this->mongoDBRaw->findMany();
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
