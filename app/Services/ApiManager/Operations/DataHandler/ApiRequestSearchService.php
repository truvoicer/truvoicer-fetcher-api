<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\S;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
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

    public function __construct(
        private Collection           $srs,
        private MongoDBRepository    $mongoDBRepository,
        private SrResponseKeyService $srResponseKeyService,
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

    public function runSingleItemSearch(string|int $itemId): array|null
    {
        $this->searchInit();
        $this->mongoDBRepository->addWhere(
            'item_id',
            $itemId,
        );
        $find = $this->mongoDBRepository->findOne();
        if ($find) {
            return $find;
        }

        $this->mongoDBRepository->addWhere(
            'item_id',
            (int)$itemId,
            '='
        );
        return $this->mongoDBRepository->findOne();
    }

    private function getOrderByFromResponseKeys(Sr $sr): string
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
    private function getOrderBy(Sr $sr): array
    {
        $sortOrder = 'desc';
        $defaultData = $sr->default_data;
        if (!is_array($defaultData) || empty($defaultData['sort_by'])) {
            $orderBy = $this->getOrderByFromResponseKeys($sr);
        } else {
            $orderBy = $defaultData['sort_by'];
        }
        if (!empty($defaultData['sort_order'])) {
            $sortOrder = $defaultData['sort_order'];
        }

        return [$orderBy, $sortOrder];

    }
    public function runListSearch(array $query): Collection|LengthAwarePaginator
    {
        $this->searchInit();
//
        $this->mongoDBRepository->setPagination(true);
        if (!empty($query[DataConstants::PAGE_SIZE])) {
            $this->mongoDBRepository->setPerPage((int)$query[DataConstants::PAGE_SIZE]);
        }

        if (!empty($query[DataConstants::PAGE_NUMBER])) {
            $this->mongoDBRepository->setPage((int)$query[DataConstants::PAGE_NUMBER]);
        }

        $reservedKeys = array_column(DataConstants::SERVICE_RESPONSE_KEYS, SResponseKeysService::RESPONSE_KEY_NAME);
        $reservedKeys = array_merge($reservedKeys, self::RESERVED_SEARCH_RESPONSE_KEYS);

        $whereGroup = [];
        $sort = [];
        foreach ($this->srs as $sr) {
            $provider = $sr->provider;
            $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                'provider',
                $provider->name,
            );

            $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                'serviceRequest.name',
                $sr->name,
            );
            list($orderBy, $sortOrder) = $this->getOrderBy($sr);
            if (!empty($orderBy) && in_array($sortOrder, $this->mongoDBRepository::AVAILABLE_ORDER_DIRECTIONS)) {
                $sort[] = [$orderBy, $sortOrder];
            }

            if (empty($query['query'])) {
                continue;
            }
            $query = $query['query'];

            $srResponseKeys = $this->srResponseKeyService->findConfigForOperationBySr($sr);

            $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                'query_params.query',
                "%{$query}%",
                'like',
                'OR'
            );
            foreach ($srResponseKeys as $srResponseKey) {
                if (in_array($srResponseKey->name, $reservedKeys)) {
                    continue;
                }
                $whereGroup[] = $this->mongoDBRepository->buildWhereData(
                    $srResponseKey->name,
                    "%{$query}%",
                    'like',
                    'OR'
                );
            }

            $this->mongoDBRepository->addWhereGroup($whereGroup, 'OR');
        }

        $query = $this->mongoDBRepository->getQuery();
        foreach ($sort as $sortData) {
            $query->orderBy($sortData[0], $sortData[1]);
        }
        return $this->mongoDBRepository->getResults(
            $query
        );
    }

    public function setSrs(Collection $srs): void
    {
        $this->srs = $srs;
    }

    public function setService(S $service): void
    {
        $this->service = $service;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
