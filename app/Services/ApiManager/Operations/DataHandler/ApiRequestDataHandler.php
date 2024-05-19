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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
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

    public function runItemSearch(array $providers, int|string $itemId): array|null
    {
        $this->searchInit('single', $providers);
        return $this->apiRequestSearchService->runSingleItemSearch($itemId);
    }

    private function buildServiceRequestQuery(Builder $query, array $data) {

        if (!empty($data['include_children']) && $data['include_children'] === true) {

        } else if (!empty($data['children']) && is_array($data['children'])) {
            $query->whereHas('childSrs', function ($query) use ($data) {
                foreach ($data['children'] as $child) {
                    $query = $this->buildServiceRequestQuery($query, $child);
                }
            });
        }
        if (!empty($data['name'])) {
            $query->where('name', $data['name']);
        }
        return $query;
    }
    private function buildServiceRequests(array $providers, string $type): void
    {
        $providerNames = array_column($providers, 'name');
        $getProviders = $this->providerService->getProviderRepository()->newQuery()
            ->whereIn('name', $providerNames)
            ->with(['sr' => function ($query) use ($type) {
                $query->whereDoesntHave('parentSrs');
            }])

            ->get();

        foreach ($getProviders as $provider) {
            $providerData = collect($providers)->firstWhere('name', $provider->name);
            $srs = $provider->sr;
//            if (
//                empty($providerData['service_request']) ||
//                !is_array($providerData['service_request']) ||
//                !count($providerData['service_request'])
//            ) {
//                $srs = $provider->sr()->get();
//            }

            if (empty($providerData['service_request']['name'])) {

            }
            if (!empty($providerData['service_request']['children']) && is_array($providerData['service_request']['children'])) {
                $query = $provider->sr()->whereHas('childSrs', function ($query) use ($providerData) {
                    foreach ($providerData['service_request']['children'] as $child) {
                        $query = $this->buildServiceRequestQuery($query, $child);
                    }
                })->get();
            }
            $srs = $provider->sr()->where('name', $providerData['service_request']['name'])->get();
            $this->srs = $this->srs->merge(
                $srs->filter(function ($sr) use ($providerData, $type) {
                    switch ($type) {
                        case 'list':
                            if ($sr->type !== 'list') {
                                return false;
                            }
                            break;
                        case 'single':
                            if ($sr->type !== 'single') {
                                return false;
                            }
                            break;
                    }

                    if (
                        empty($providerData['service_request']['name']) &&
                        $sr->default_sr === true
                    ) {
                        return true;
                    }
                    if (
                        !empty($providerData['service_request']['name']) &&
                        $sr->name === $providerData['service_request']['name']
                    ) {
                        return true;
                    }
                    return false;
                })
            );
        }
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
            case 'list':
                $results = $this->runListSearch(
                    $providerData,
                    $data
                );
                return new ApiSearchListResourceCollection($results);
            case 'single':
                $results = $this->runItemSearch(
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
