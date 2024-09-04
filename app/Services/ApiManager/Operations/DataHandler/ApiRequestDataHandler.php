<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\User;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use App\Traits\User\UserTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    use UserTrait;
    public function __construct(
        protected EloquentCollection $srs,
        protected ProviderService    $providerService,
        protected CategoryService $categoryService,
        protected ApiService $apiService,
        protected ApiResponse $apiResponse,
        protected ApiRequestService $apiRequestService,
    )
    {
    }

    protected function getCategory(?array $data): ?Model
    {
        if (!empty($data['category'])) {
            $category = $this->categoryService->getCategoryRepository()->findByName($data['category']);
            if (!$category) {
                throw new BadRequestHttpException("S not found");
            }
            if ($this->user->cannot('view', $category)) {
                throw new BadRequestHttpException(sprintf(
                    "Permission denied to view category %s",
                    $category->name
                ));
            }
            return $category;
        }
        return null;
    }
    protected function getService(?array $data): ?Model
    {
        if (!empty($data['service'])) {
            $service = $this->apiService->getServiceRepository()->findByName($data['service']);
            if (!$service) {
                throw new BadRequestHttpException("Service not found");
            }
            if ($this->user->cannot('view', $service)) {
                throw new BadRequestHttpException(sprintf(
                    "Permission denied to view service %s",
                    $service->name
                ));
            }
            return $service;
        }
        return null;
    }

    protected function buildServiceRequests(array $providers, string $type): void
    {
        switch ($type) {
            case 'list':
                $this->buildSrsForList($providers, $type);
                break;
            case 'mixed':
                $this->buildSrsForMixedSearch($providers, $type);
                break;
            default:
                throw new BadRequestHttpException("Invalid type");
        }
    }
    protected function buildSrsForMixedSearch(array $providers, string $type): array
    {
        $buildProviders = [];
        foreach ($providers as $provider) {
            if (empty($provider['provider_name'])) {
                continue;
            }
            if (empty($provider['item_id'])) {
                continue;
            }
            $findIndex = array_search($provider['provider_name'], array_column($buildProviders, 'provider_name'));
            if ($findIndex === false) {
                $buildProviders[] = [
                    'provider_name' => $provider['provider_name'],
                    'ids' => [$provider['item_id']]
                ];
                continue;
            }
            $buildProviders[$findIndex]['ids'][] = $provider['item_id'];
        }
        return $buildProviders;
    }

    protected function buildSrsForList(array $providers, string $type): void
    {
        $providerNames = array_column($providers, 'provider_name');
        if (!count($providerNames)) {
            $providerNames = array_column($providers, 'name');
        }

        $getProviders = $this->providerService->getProviderRepository()->newQuery()
            ->whereIn('name', $providerNames)
            ->with(['sr' => function ($query) use ($type) {
                $query->whereDoesntHave('parentSrs')
                    ->where('type', $type);
            }])
            ->whereHas('sr', function ($query) use ($type) {
                $query->whereDoesntHave('parentSrs')
                    ->where('type', $type);
            })
            ->orderBy('name', 'asc')
            ->get();
        foreach ($getProviders as $provider) {
            $providerData = collect($providers)->firstWhere('name', $provider->name);
            if (
                empty($providerData['service_request']) ||
                !is_array($providerData['service_request']) ||
                !count($providerData['service_request'])
            ) {
                $this->setServiceRequests($type, $provider->sr);
                return;
            } else {
                $data = $this->flattenServiceRequestData([$providerData['service_request']]);

                if (!count($data)) {
                    continue;
                }
                $data = $this->buildQueryData($data, $data);
                $query = Provider::where('name', $provider->name)
                    ->whereHas('sr', function ($query) use ($type, $provider, $data) {
                    $query->whereDoesntHave('parentSrs');
                    $query = $this->buildServiceRequestQuery(
                        $type,
                        $query,
                        $data
                    );
                })
                ->with(['sr' => function ($query) use ($type, $provider, $data) {
                    $query->whereDoesntHave('parentSrs');
                    $query = $this->buildServiceRequestQuery(
                        $type,
                        $query,
                        $data
                    );
                }]);
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
        dd($srs->toArray());
        foreach ($srs as $sr) {
            $this->srs->push($sr);
            if ($sr->childSrs->count() > 0) {
                $this->flattenSrCollection($type, $sr->childSrs);
            }
        }
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
    private function hasIncludeChildren(array $data, string $name): bool
    {
        return (
            (empty($data['children']) || !is_array($data['children'])) &&
            !empty($data['include_children']) &&
            is_array($data['include_children']) &&
            in_array($name, $data['include_children'])
        );
    }
    private function dontIncludeChildren(array $data, string $name): bool
    {
        return (
            (empty($data['children']) || !is_array($data['children'])) &&
            !empty($data['not_include_children']) &&
            is_array($data['not_include_children']) &&
            in_array($name, $data['not_include_children'])
        );
    }
    private function buildServiceRequestQuery(string $type, HasMany|BelongsToMany|Builder $query, array $data, ?array $excludeChildren = [], ?array $includeChildren = []): HasMany|BelongsToMany|Builder
    {

        $query->where('type', $type);
        if (count($excludeChildren)) {
            foreach ($excludeChildren as $index => $name) {
                $query->where('name', '<>', $name);
            }
        }
        $names = [];
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
        $query->with('childSrs', function ($query) use ($data, $names) {
            foreach ($names as $index => $name) {
                if ($index === 0) {
                    if ($this->hasIncludeChildren($data, $name)) {
                        $query->whereHas('parentSrs', function ($query) use ($name) {
                            $query->where('name', $name);
                        });
                    }
                } else {
                    if ($this->hasIncludeChildren($data, $name)) {
                        $query->orWhereHas('parentSrs', function ($query) use ($name) {
                            $query->where('name', $name);
                        });
                    }
                }
            }
        });
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
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->apiRequestService->setUser($user);
    }
}
