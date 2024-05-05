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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    private ApiResponse $apiResponse;

    public function __construct(
        private ApiRequestService       $requestOperation,
        private MongoDBRepository       $mongoDBRepository,
        private ProviderService         $providerService,
        private ApiRequestSearchService $apiRequestSearchService,
        private SrService               $srService,
        private Provider                $provider,
        private Sr                      $sr,
        private User                    $user,
    )
    {
        $this->apiResponse = new ApiResponse();
    }

    public function searchInit(string $providerName, string $srName, ?array $query = []): void
    {
        $findProvider = $this->findProviderByName($providerName);

        if (!$findProvider instanceof Provider) {
            throw new BadRequestHttpException("Provider not found");
        }
        $this->setProvider($findProvider);
        $this->apiRequestSearchService->setProvider($findProvider);
        $sr = $this->findSrByName($srName);
        if (!$sr instanceof Sr) {
            throw new BadRequestHttpException("Service request {$srName} not found");
        }
        $this->setSr($sr);

        $this->apiRequestSearchService->setSr($this->sr);

    }

    public function runListSearch(string $providerName, string $srName, ?array $query = []): Collection|LengthAwarePaginator
    {
        $this->searchInit($providerName, $srName, $query);
        return $this->apiRequestSearchService->runListSearch($query);
    }
    public function runItemSearch(string $providerName, string $srName, int|string $itemId): array|null
    {
        $this->searchInit($providerName, $srName);
        return $this->apiRequestSearchService->runSingleItemSearch($itemId);
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
