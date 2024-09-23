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
use Illuminate\Support\Arr;

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

    private function prepareSearchForSavedProviders(array $queryData)
    {

        $reservedKeys = array_column(DataConstants::SERVICE_RESPONSE_KEYS, SResponseKeysService::RESPONSE_KEY_NAME);
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
        foreach ($this->providers as $provider) {
            $whereGroup = [];
            $srs = $this->srService->flattenSrCollection($this->type, $provider->sr);
            if ($srs->count() === 0) {
                continue;
            }

            $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                'provider',
                $provider->name,
            );
            $responseKeyWhereGroup = [];
            foreach ($srs as $sr) {
//                $whereGroup[] = $this->mongoDBRepository->buildWhereData(
//                    'serviceRequest.name',
//                    $sr->name,
//                );
                list($orderBy, $sortOrder) = $this->getOrderBy($sr, $orderByData);
                if (!empty($orderBy) && in_array($sortOrder, $this->mongoDBRepository::AVAILABLE_ORDER_DIRECTIONS)) {
                    $sort[] = [$orderBy, $sortOrder];
                }


                if (!empty($queryData['query'])) {
                    $responseKeyWhereGroup[] = $this->mongoDBRepository->buildWhereData(
                        'query_params.query',
                        $queryData['query'],
                        'like',
                        'OR'
                    );
                }
                $excludeKeys = [
                    'sort_by',
                    'sort_order',
                    'page_size',
                    'page_number',
                    ...array_map(fn ($val) => $val[SResponseKeysService::RESPONSE_KEY_NAME],
                        array_merge(array_values(Arr::collapse(DataConstants::S_RESPONSE_KEY_GROUPS)))
                    )
                ];
                $srResponseKeys = $this->srResponseKeyService->findResponseKeysForOperationBySr(
                    $sr,
                    $excludeKeys,
                    ['searchable' => true]
                );
                foreach ($queryData as $key => $queryItem) {
                    foreach ($srResponseKeys as $srResponseKey) {
                        if (in_array($srResponseKey->name, $reservedKeys)) {
                            continue;
                        }
                        if (is_array($queryItem)) {
                            $queryItemArray = array_filter($queryItem, function ($item) {
                                return (is_string($item) || is_numeric($item));
                            });
                            foreach ($queryItemArray as $queryItemArrayItem) {
                                $responseKeyWhereGroup[] = $this->mongoDBRepository->buildWhereData(
                                    $srResponseKey->name,
                                    "%{$queryItemArrayItem}%",
                                    'like',
                                    'OR'
                                );
                            }
                        } elseif (is_string($queryItem) || is_numeric($queryItem)) {
                            $responseKeyWhereGroup[] = $this->mongoDBRepository->buildWhereData(
                                $srResponseKey->name,
                                "%{$queryItem}%",
                                'like',
                                'OR'
                            );
                        }
                    }
                }
                if (count($responseKeyWhereGroup)) {
                    $whereGroup[] = $this->mongoDBRepository->buildSubWhereGroup(
                        $responseKeyWhereGroup,
                    );
                }
                $this->mongoDBRepository->addWhereGroup($whereGroup, 'OR');
            }
        }


        foreach ($sort as $sortData) {
            $this->mongoDBRepository->addOrderBy($sortData[0], $sortData[1]);
        }
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

        if (!empty($this->itemSearchData) && count($this->itemSearchData)) {
            $this->prepareItemSearch($this->type, $this->itemSearchData);
            $this->preparePagination($queryData);
            return $this->mongoDBRepository->findMany();
        }
        $this->searchInit();

        $this->preparePagination($queryData);
        $this->prepareSearchForSavedProviders(
            $queryData
        );
        return $this->mongoDBRepository->getResults(
            $this->mongoDBRepository->getQuery()
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
