<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiSearchItemResource;
use App\Http\Resources\ApiSearchListResourceCollection;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    private ApiResponse $apiResponse;

    public function __construct(
        private EloquentCollection $providers,
        private ProviderService         $providerService,
        private ApiRequestSearchService $apiRequestSearchService,
        private SrService               $srService,
        private ApiService               $apiService,
        private Provider                $provider,
        private Sr                      $sr,
        private User                    $user,
        private S $service
    )
    {
        $this->apiResponse = new ApiResponse();
    }

    public function searchInit(string $type, array $providers, ?string $srName, ?array $query = []): void
    {
        $findProvider = $this->findProviders($providers);
        if ($findProvider->isEmpty()) {
            throw new BadRequestHttpException("Providers not found");
        }
        $this->providers = $findProvider;
        $this->apiRequestSearchService->setProviders($findProvider);
        $this->apiRequestSearchService->setService($this->service);
        $this->apiRequestSearchService->setType($type);
        switch ($type) {
            case 'list':
                break;
            case 'single':
                if (empty($srName)) {
                    $srName =  'default';
                    $sr = $this->srService->getDefaultSr($this->provider, $type);
                } else {
                    $sr = $this->findSrByName($srName);
                }
                if (!$sr instanceof Sr) {
                    throw new BadRequestHttpException("Service request {$srName} not found");
                }
                $this->setSr($sr);
                $this->apiRequestSearchService->setSr($this->sr);
                break;
            default:
                throw new BadRequestHttpException("Invalid search type");
        }


    }

    public function runListSearch(array $providers, ?string $srName, ?array $query = []): Collection|LengthAwarePaginator
    {
        $this->searchInit('list', $providers, $srName, $query);
        return $this->apiRequestSearchService->runListSearch($query);
    }
    public function runItemSearch(array $providers, ?string $srName, int|string $itemId): array|null
    {
        $this->searchInit('single', $providers, $srName);
        return $this->apiRequestSearchService->runSingleItemSearch($itemId);
    }
    private function findProviders(array $providers): EloquentCollection
    {
        $providerNames = array_column($providers, 'name');
        $getProviders = $this->providerService->getProviderRepository()->newQuery()
            ->whereIn('name', $providerNames)
            ->with('sr')->get();

        return $getProviders->transform(function (Provider $provider) use ($providers) {
            $providerData = collect($providers)->firstWhere('name', $provider->name);
            $provider->setAttribute('sr', $provider->sr->filter(function (Sr $sr) use ($providerData) {
                return ($sr->name === $providerData['service_request_name']);
            }));


            return $provider;
        });
    }
    private function findProviderByName(string $providerName): Provider|bool
    {
        $provider = $this->providerService->getUserProviderByName($this->user, $providerName);
        if (!$provider instanceof Provider) {
            return false;
        }
        return $provider;
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
        $serviceRequestName = null;
        if (!empty($providerData['service_request_name'])) {
            $serviceRequestName = $providerData['service_request_name'];
        }
        $providerData = $this->buildProviderData($providers);
        switch ($type) {
            case 'list':
                $results = $this->runListSearch(
                    $providerData,
                    $serviceRequestName,
                    $data
                );
                return new ApiSearchListResourceCollection($results);
            case 'single':
                $results = $this->runItemSearch(
                    $providerData,
                    $serviceRequestName,
                    $data['item_id']
                );
                return new ApiSearchItemResource($results);
            default:
                return false;
        }
    }
    private function findSrByName(string $srName): Sr|false
    {
        $sr = $this->srService->getRequestByName($this->provider, $srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        return $sr;
    }
    private function findService(string $serviceName): S|false
    {
        $sr = $this->apiService->getServiceRepository()->findByName($serviceName);
        if (!$sr instanceof S) {
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

    public function setService(S $service): void
    {
        $this->service = $service;
    }

}
