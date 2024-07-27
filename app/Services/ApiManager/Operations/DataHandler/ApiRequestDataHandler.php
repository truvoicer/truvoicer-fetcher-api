<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ApiRequestDataHandler
{
    public function __construct(
        private readonly EloquentCollection $srs,
        private readonly ProviderService    $providerService
    )
    {
    }

    protected function buildServiceRequests(array $providers, string $type): void
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
}
