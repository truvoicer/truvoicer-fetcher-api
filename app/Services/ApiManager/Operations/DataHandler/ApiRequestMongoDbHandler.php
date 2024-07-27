<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiSearchItemResource;
use App\Http\Resources\ApiSearchListResourceCollection;
use App\Models\S;
use App\Models\User;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestMongoDbHandler extends ApiRequestDataHandler
{
    private ApiResponse $apiResponse;

    public function __construct(
        private EloquentCollection      $srs,
        private ProviderService         $providerService,
        private ApiRequestSearchService $apiRequestSearchService,
        private ApiService              $apiService,
        private S                       $service
    )
    {
        parent::__construct($srs, $providerService);
        $this->apiResponse = new ApiResponse();
    }

    public function searchInit(string $type, array $providers): void
    {
        $this->buildServiceRequests($providers, $type);
        if ($this->srs->count() === 0) {
            throw new BadRequestHttpException("Providers not found");
        }
        $this->apiRequestSearchService->setSrs($this->srs);
        $this->apiRequestSearchService->setService($this->service);
        $this->apiRequestSearchService->setType($type);

    }

    public function runListSearch(array $providers, ?array $query = []): Collection|LengthAwarePaginator
    {
        $this->searchInit('list', $providers);
        return $this->apiRequestSearchService->runListSearch($query);
    }

    public function runItemSearch(string $type, array $providers, int|string $itemId): array|null
    {
        $this->searchInit($type, $providers);
        return $this->apiRequestSearchService->runSingleItemSearch($itemId);
    }

    private function isSingleProvider(array $data): bool
    {
        if (!count($data)) {
            return false;
        }
        return (!is_array($data[array_key_first($data)]));
    }

    private function buildProviderData(array $data): array
    {
        if ($this->isSingleProvider($data)) {
            return [$data];
        }
        return $data;
    }

    public function searchOperation(string $type, array $providers, string $serviceName, ?array $data = [])
    {
        if (!count($providers)) {
            return false;
        }
        $getService = $this->findService($serviceName);
        if (!$getService instanceof S) {
            return false;
        }
        $this->setService($getService);
        $providerData = $this->buildProviderData($providers);
        switch ($type) {
            case SrRepository::SR_TYPE_LIST:
                $results = $this->runListSearch(
                    $providerData,
                    $data
                );
                return new ApiSearchListResourceCollection($results);
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                $results = $this->runItemSearch(
                    $type,
                    $providerData,
                    $data['item_id']
                );
                return new ApiSearchItemResource($results);
            default:
                return false;
        }
    }


    private function findService(string $serviceName): S|false
    {
        $sr = $this->apiService->getServiceRepository()->findByName($serviceName);
        if (!$sr instanceof S) {
            return false;
        }
        return $sr;
    }


    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setService(S $service): void
    {
        $this->service = $service;
    }

}
