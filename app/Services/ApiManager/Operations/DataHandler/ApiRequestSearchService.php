<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestSearchService
{
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

    public function runSearch(?array $query = []): \MongoDB\Laravel\Collection|LengthAwarePaginator
    {
        $this->requestOperation->setQueryArray($query);
        $collectionName = $this->mongoDBRepository->getCollectionName($this->sr);


        $this->srResponseKeys = $this->srResponseKeyService->findConfigForOperationBySr($this->sr);

        $this->mongoDBRepository->setCollection($collectionName);


        $this->buildSearchData($query);
        return $this->mongoDBRepository->findMany();
    }

    private function buildSearchData(array $query): void
    {
        $searchData = [];
        if (!empty($query['query'])) {
            $query = $query['query'];
        }
        $this->mongoDBRepository->addWhere(
            'query_params.query',
            $query,
            'OR'
        );
        $this->srResponseKeys->each(function ($srResponseKey) use ($query) {
            $this->mongoDBRepository->addWhere(
                $srResponseKey->name,
                $query,
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
