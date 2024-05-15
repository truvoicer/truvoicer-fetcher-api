<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Library\Defaults\DefaultData;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRepository;
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

    public function runListSearch(array $query): Collection|LengthAwarePaginator
    {
        $this->searchInit();
//
        $this->mongoDBRepository->setPagination(true);
        if (!empty($query[DefaultData::PAGE_SIZE])) {
            $this->mongoDBRepository->setPerPage((int)$query[DefaultData::PAGE_SIZE]);
        }

        if (!empty($query[DefaultData::PAGE_NUMBER])) {
            $this->mongoDBRepository->setPage((int)$query[DefaultData::PAGE_NUMBER]);
        }

        $reservedKeys = array_column(DefaultData::SERVICE_RESPONSE_KEYS, SResponseKeysService::RESPONSE_KEY_NAME);
        $reservedKeys = array_merge($reservedKeys, self::RESERVED_SEARCH_RESPONSE_KEYS);

        $whereGroup = [];
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
//                $dateKeys = $this->srResponseKeys->filter(function ($srResponseKey) {
//                    return str_contains($srResponseKey->name, 'date');
//                });
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

        }

        $this->mongoDBRepository->addWhereGroup($whereGroup, 'OR');

        return $this->mongoDBRepository->findMany();
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
