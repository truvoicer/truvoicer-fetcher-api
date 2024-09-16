<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\User;
use App\Repositories\SrRepository;
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

    protected array $notFoundProviders;
    protected array $itemSearchData;

    public function __construct(
        protected EloquentCollection $providers,
        protected ProviderService    $providerService,
        protected CategoryService    $categoryService,
        protected ApiService         $apiService,
        protected ApiResponse        $apiResponse,
        protected ApiRequestService  $apiRequestService,
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

    protected function prepareProviders(array $providers, string $type): void
    {
        switch ($type) {
            case 'mixed':
            case 'list':
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                $this->buildSrsForList($providers, $type);
                $this->notFoundProviders = $this->prepareNotFoundProviders($providers);
                break;
            default:
                throw new BadRequestHttpException("Invalid type");
        }
        $this->itemSearchData = $this->buildSrsForMixedSearch($providers, $type);
    }

    protected function buildSrsForMixedSearch(array $providers): array
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
                    'ids' => []
                ];
                $findIndex = array_search($provider['provider_name'], array_column($buildProviders, 'provider_name'));
            }
            if (is_array($provider['item_id'])) {
                $buildProviders[$findIndex]['ids'] = array_merge($buildProviders[$findIndex]['ids'], $provider['item_id']);
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
                $this->providers->push($provider);
                return;
            } else {
                $data = $this->prepareSrRequestData($providerData['service_request']);

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
            $provider = $query->first();
            if (!$provider instanceof Provider) {
                continue;
            }
            $this->providers->push($provider);
        }
    }

    protected function prepareNotFoundProviders(array $providers)
    {
        $providers = array_filter($providers, function ($provider) {
            return !!(!empty($provider['provider_name']) && $this->providers->contains('name', $provider['provider_name']));
        });

        return array_values(
            array_map(function ($provider) {
                if (
                    !empty($provider['service_request']) &&
                    is_array($provider['service_request']) &&
                    count($provider['service_request'])
                ) {
                    $provider['service_request'] = $this->flattenSrRequestData($provider['service_request']);
                }
                return $provider;
            }, $providers)
        );
    }

    protected function flattenSrRequestData(array $children, array $data = [])
    {
        foreach ($children as $index => $child) {
            if (!empty($child['name'])) {
                $data[] = ['name' => $child['name']];
            }
            if (isset($child['include_children']) && $child['include_children'] === false) {
                continue;
            }
            if (!empty($child['children']) && is_array($child['children'])) {
                $data = $this->flattenSrRequestData($child['children'], $data);
            }
        }
        return $data;
    }

    protected function prepareSrRequestData(array $children, array $data = [], int $i = 0)
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
                $data = $this->prepareSrRequestData($child['children'], $data, ($i + 1));
            }
        }
        return $data;
    }

    protected function buildQueryData(array $origData, array $data, ?int $step = 0): array
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

    private function hasChildren(array $data): bool
    {
        return (
            !empty($data['children']) &&
            is_array($data['children']) &&
            count($data['children'])
        );
    }

    private function includeSrChildrenQuery(array $names, HasMany|BelongsToMany|Builder $query, ?bool $orWhere = false)
    {
        foreach ($names as $index => $name) {
            if ($index === 0 && !$orWhere) {
                $query->whereHas('parentSrs', function ($query) use ($name) {
                    $query->where('name', $name);
                });
            } else {
                $query->orWhereHas('parentSrs', function ($query) use ($name) {
                    $query->where('name', $name);
                });
            }
        }
        return $query;
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
        $includeChildrenNames = array_filter($names, function ($name) use ($data) {
            return $this->hasIncludeChildren($data, $name);
        });

        if (count($includeChildren)) {
            $query = $this->includeSrChildrenQuery($includeChildren, $query, true);
        }
        if (!$this->hasChildren($data) && count($includeChildrenNames)) {
            $query->with('childSrs', function ($query) use ($includeChildrenNames) {
                $query = $this->includeSrChildrenQuery($includeChildrenNames, $query);
            });
        } elseif (!$this->hasChildren($data) && !count($includeChildrenNames)) {
            $query->without('childSrs');
        }
        if ($this->hasChildren($data)) {
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

    public function setItemSearchData(array $itemSearchData): void
    {
        $this->itemSearchData = $itemSearchData;
    }

}
