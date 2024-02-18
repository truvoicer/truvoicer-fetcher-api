<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    private ApiResponse $apiResponse;
    public function __construct(
        private ApiRequestService $requestOperation,
        private MongoDBRepository $mongoDBRepository,
        private ProviderService $providerService,
        private ApiRequestSearchService $apiRequestSearchService,
        private SrService $srService,
        private Provider $provider,
        private Sr $sr,
        private User $user,
    )
    {
        $this->apiResponse = new ApiResponse();
    }

    public function runSearch(string $providerName, string $srName, ?array $query = []): ApiResponse|false
    {
        $findProvider = $this->findProviderByName($providerName);
        if (!$findProvider instanceof Provider) {
            return false;
        }
        $this->setProvider($findProvider);
        $this->apiRequestSearchService->setProvider($findProvider);
        $sr = $this->findSrByName($srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        $this->setSr($sr);

        $this->apiRequestSearchService->setSr($this->sr);

        $this->apiResponse->setRequestType('search');
        $this->apiResponse->setCategory($this->sr->category->name);
        $this->apiResponse->setProvider($this->provider->name);
        $this->apiResponse->setMessage('Search Results');
        $this->apiResponse->setContentType('json');
        $this->apiResponse->setRequestService($this->sr->name);
        $this->apiResponse->setExtraData([]);
        $this->apiResponse->setPaginationType($this->sr->pagination_type);
        $this->apiResponse->setStatus('success');
        $this->apiResponse->setRequestData($this->apiRequestSearchService->runSearch($query));

        return $this->apiResponse;
    }

    private function buildResponse($results) {

    }


    private function findProviderByName(string $providerName): Provider|bool
    {
        $provider = $this->providerService->getUserProviderByName($this->user, $providerName);
        if (!$provider instanceof Provider) {
            return false;
        }
        return $provider;
    }
    private function findSrByName(string $srName): Sr|false
    {
        $sr = $this->srService->getRequestByName($this->provider, $srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        return $sr;
    }
    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setSr(Sr $sr): void
    {
        $this->sr = $sr;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
