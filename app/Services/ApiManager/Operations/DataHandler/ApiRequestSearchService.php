<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\S;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiRequestSearchService
{
    const RESERVED_SEARCH_RESPONSE_KEYS = [
        'items_array',
    ];
    private Collection $srResponseKeys;
    private string $type;
    protected array $notFoundProviders;
    protected array $itemSearchData;

    public function __construct(
        private Collection           $providers,
        private MongoDBRepository    $mongoDBRepository,
        private SrResponseKeyService $srResponseKeyService,
        private SrService            $srService,
        private S                    $service,
    )
    {
    }

    public function searchInit(): void
    {
        $collectionName = $this->mongoDBRepository->getCollectionNameByService(
            $this->service,
            $this->type
        );

        $this->mongoDBRepository->setCollection($collectionName);

    }

    private function prepareItemSearch(string $type, array $providers): void
    {
        $this->type = $type;
        $this->searchInit();
        foreach ($providers as $provider) {
            $providerName = $provider['provider_name'];
            $itemIds = $provider['ids'];
            $this->mongoDBRepository->addWhereGroup([
                $this->mongoDBRepository->buildWhereData(
                    'item_id',
                    $itemIds,
                    'IN'
                ),
                $this->mongoDBRepository->buildWhereData(
                    'item_id',
                    array_map('intval', $itemIds),
                    'IN',
                    'OR'
                ),
                ...array_map(function ($itemId) {
                    return $this->mongoDBRepository->addMatchArrayElement(
                        'item_id',
                        ['data' => (int)$itemId],
                        'OR'
                    );
                }, $itemIds),
            ]);
            $this->mongoDBRepository->addWhereGroup([
                $this->mongoDBRepository->buildWhereData(
                    'provider',
                    $providerName,
                )
            ]);
        }
    }

    public function runSingleItemSearch(string $type): array|null
    {
        $this->prepareItemSearch($type, $this->itemSearchData);
        return $this->mongoDBRepository->findOne();
    }

    private function getOrderByFromResponseKeys(Sr $sr): string|null
    {
        $orderBy = null;
        $srResponseKeys = $this->srResponseKeyService->findConfigForOperationBySr($sr);
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

    private function prepareSearchForSavedProviders($query, array $queryData)
    {

        $reservedKeys = array_column(DataConstants::SERVICE_RESPONSE_KEYS, SResponseKeysService::RESPONSE_KEY_NAME);
        $reservedKeys = array_merge($reservedKeys, self::RESERVED_SEARCH_RESPONSE_KEYS);
        $whereGroup = [];
        $sort = [];
        foreach ($this->providers as $provider) {
            $srs = $this->srService->flattenSrCollection($this->type, $provider->sr);
            if ($srs->count() === 0) {
                continue;
            }
            foreach ($srs as $sr) {
                $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                    'provider',
                    $provider->name,
                );

                $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                    'serviceRequest.name',
                    $sr->name,
                );
                list($orderBy, $sortOrder) = $this->getOrderBy($sr, $queryData);
                if (!empty($orderBy) && in_array($sortOrder, $this->mongoDBRepository::AVAILABLE_ORDER_DIRECTIONS)) {
                    $sort[] = [$orderBy, $sortOrder];
                }

                if (empty($queryData['query'])) {
                    continue;
                }
                $queryData = $queryData['query'];

                $srResponseKeys = $this->srResponseKeyService->findConfigForOperationBySr($sr);

                $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                    'query_params.query',
                    "%{$queryData}%",
                    'like',
                    'OR'
                );
                foreach ($srResponseKeys as $srResponseKey) {
                    if (in_array($srResponseKey->name, $reservedKeys)) {
                        continue;
                    }
                    $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                        $srResponseKey->name,
                        "%{$queryData}%",
                        'like',
                        'OR'
                    );
                }

                $this->mongoDBRepository->addWhereGroup($whereGroup, 'OR');
            }
        }
        foreach ($sort as $sortData) {
            $query->orderBy($sortData[0], $sortData[1]);
        }
        return $query;
    }

    private function preparePagination(array $query): void
    {
        $this->mongoDBRepository->setPagination(true);
        if (!empty($query[DataConstants::PAGE_SIZE])) {
            $this->mongoDBRepository->setPerPage((int)$query[DataConstants::PAGE_SIZE]);
        }

        if (!empty($query[DataConstants::PAGE_NUMBER])) {
            $this->mongoDBRepository->setPage((int)$query[DataConstants::PAGE_NUMBER]);
        }
    }

    public function runListSearch(array $queryData): Collection|LengthAwarePaginator
    {
        $results = [];
        if (!empty($this->itemSearchData) && count($this->itemSearchData)) {
            $this->prepareItemSearch($this->type, $this->itemSearchData);
            $this->preparePagination($queryData);
            return $this->mongoDBRepository->findMany();
        }
        $this->searchInit();

        $this->preparePagination($queryData);
        $query = $this->prepareSearchForSavedProviders(
            $this->mongoDBRepository->getQuery(),
            $queryData
        );
        return $this->mongoDBRepository->getResults(
            $query
        );

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
