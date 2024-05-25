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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    private function buildServiceRequestQuery(string $type, HasMany|BelongsToMany|Builder $query, array $data, ?array $excludeChildren = [], ?array $includeChildren = []): HasMany|BelongsToMany|Builder
    {
        $query->where('type', $type);
        if (count($excludeChildren)) {
            foreach ($excludeChildren as $index => $name) {
                $query->where('name', '<>', $name);
            }
        }
        $names = $includeChildren;
        if (!empty($data['name']) && is_array($data['name'])) {
            $names = array_merge($names, $data['name']);
        }
        foreach ($names as $index => $name) {
            if ($index === 0) {
                $query->where('name', $name);
            } else {
                $query->orWhere('name', $name);
            }
        }
        if (!empty($data['children']) && is_array($data['children'])) {
            $query->with('childSrs', function ($query) use ($type, $data, $excludeChildren, $includeChildren) {
                $query = $this->buildServiceRequestQuery(
                    $type,
                    $query,
                    $data['children'],
                    (!empty($data['not_include_children']) && is_array($data['not_include_children']))
                        ? array_merge($data['not_include_children'], $excludeChildren)
                        : $excludeChildren,
                    (!empty($data['include_children']) && is_array($data['include_children']))
                        ? array_merge($data['include_children'], $includeChildren)
                        : $includeChildren
                );
            });
        }
        return $query;
    }


    private function flattenServiceRequestData(array $children, array $data = [], int $i = 0)
    {
        foreach ($children as $index => $child) {
            if (!empty($child['name'])) {
                $data[$i]['name'][] = $child['name'];
            }
            if (!empty($child['include_children']) && $child['include_children'] === true) {
                $data[$i]['include_children'][] = $child['name'];
            }
            if (isset($child['include_children']) && $child['include_children'] === false) {
                $data[$i]['not_include_children'][] = $child['name'];
            }
            if (!empty($child['children']) && is_array($child['children'])) {
                $data = $this->flattenServiceRequestData($child['children'], $data, ($i + 1));
            }
        }
        return $data;
    }

    private function buildQueryData(array $origData, array $data, ?int $step = 0): array
    {
        $buildData = [];
        $child = $origData[$step];
        $buildData['name'] = $child['name'];
        if (!empty($child['include_children'])) {
            $buildData['include_children'] = $child['include_children'];
        }
        if (!empty($child['not_include_children'])) {
            $buildData['not_include_children'] = $child['not_include_children'];
        }
        if ($step === count($origData) - 1) {
            return $buildData;
        }
        $buildData['children'] = $this->buildQueryData($origData, [$origData[$step]], $step + 1);

        return $buildData;
    }

    private function buildServiceRequests(array $providers, string $type): void
    {
        $providerNames = array_column($providers, 'provider_name');
        if (!count($providerNames)) {
            $providerNames = array_column($providers, 'name');
        }

        $getProviders = $this->providerService->getProviderRepository()->newQuery()
            ->whereIn('name', $providerNames)
            ->with(['sr' => function ($query) use ($type) {
                $query->whereDoesntHave('parentSrs');
            }])
            ->orderBy('name', 'asc')
            ->get();

        foreach ($getProviders as $provider) {
            $providerData = collect($providers)->firstWhere('name', $provider->name);
            if (
                empty($providerData['service_request']) ||
                !is_array($providerData['service_request']) ||
                !count($providerData['service_request'])
            ) {
                $srs = $provider->sr()->get();
                $this->setServiceRequests($type, $srs);
                return;
            } else {
                $data = $this->flattenServiceRequestData($providerData['service_request']);
                $data = $this->buildQueryData($data, $data);
                $query = $this->buildServiceRequestQuery(
                    $type,
                    $provider->sr()
                        ->whereDoesntHave('parentSrs'),
                    $data
                );
            }
            $this->setServiceRequests($type, $query->get());
        }
    }

    private function flattenSrCollection(string $type, Collection $srs)
    {
        foreach ($srs as $sr) {
            if ($sr->type === $type) {
                $this->srs->push($sr);
            }
            if ($sr->childSrs->count() > 0) {
                $this->flattenSrCollection($type, $sr->childSrs);
            }
        }
    }

    private function setServiceRequests(string $type, Collection $srs)
    {
        foreach ($srs as $sr) {
            $this->srs->push($sr);
            if ($sr->childSrs->count() > 0) {
                $this->flattenSrCollection($type, $sr->childSrs);
            }
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
