<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Library\Defaults\DefaultData;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestSearchService
{
    const RESERVED_SEARCH_RESPONSE_KEYS = [
        'items_array',
    ];
    private Collection $srResponseKeys;

    public function __construct(
        private ApiRequestService $requestOperation,
        private MongoDBRepository $mongoDBRepository,
        private ProviderService $providerService,
        private SrResponseKeyService $srResponseKeyService,
        private SrService $srService,
        private Provider $provider,
        private Sr $sr
    )
    {
    }

    public function runSearch(?array $query = []): SupportCollection|LengthAwarePaginator
    {
        $this->requestOperation->setQueryArray($query);
        $collectionName = $this->mongoDBRepository->getCollectionName($this->sr);

        $this->mongoDBRepository->setPagination(true);

        $this->srResponseKeys = $this->srResponseKeyService->findConfigForOperationBySr($this->sr);

        $this->mongoDBRepository->setCollection($collectionName);

        $this->buildSearchData($query);
        return $this->mongoDBRepository->findMany();
    }

    private function buildSearchData(array $query): void
    {
        $searchData = [];
        if (empty($query['query'])) {
            return;
        }
        $query = $query['query'];
        $this->mongoDBRepository->addWhere(
            'query_params.query',
            "%{$query}%",
            'like',
            'OR'
        );
        $reservedKeys = array_column(DefaultData::SERVICE_RESPONSE_KEYS, SResponseKeysService::RESPONSE_KEY_NAME);
        $reservedKeys = array_merge($reservedKeys, self::RESERVED_SEARCH_RESPONSE_KEYS);
        $this->srResponseKeys->each(function ($srResponseKey) use ($query, $reservedKeys) {
            if (in_array($srResponseKey->name, $reservedKeys)) {
                return;
            }
            $this->mongoDBRepository->addWhere(
                $srResponseKey->name,
                "%{$query}%",
                'like',
                'OR'
            );
        });
    }
    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setSr(Sr $sr): void
    {
        $this->sr = $sr;
    }

}
