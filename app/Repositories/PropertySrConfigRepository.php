<?php

namespace App\Repositories;

use Truvoicer\TruFetcherGet\Models\Property;
use Truvoicer\TruFetcherGet\Models\PropertySrConfig;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\ProviderProperty;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PropertySrConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(PropertySrConfig::class);
    }

    public function getModel(): PropertySrConfig
    {
        return parent::getModel();
    }

    public function findProviderProperties(Provider $provider, array $properties)
    {
        $property = new Property();
        $query = $property->query()
            ->whereIn('name', $properties)
            ->with(['providerProperty' => function (HasOne $query) use ($provider) {
                $query->where('provider_id', '=', $provider->id);
            }]);
        return $this->getResults($query);
    }

    public function findProviderPropertyWithRelation(Provider $provider, Property $property)
    {
        return Property::with(['providerProperty' => function (HasOne $query) use ($provider, $property) {
            $query->where('provider_id', '=', $provider->id)
                ->where('property_id', '=', $property->id);
        }])
            ->where('id', '=', $property->id)
            ->first();
    }

    public function findProviderProperty(Provider $provider, Property $property)
    {
        return $provider->properties()
            ->where('property_id', '=', $property->id)
            ->with('providerProperty')
            ->first();
    }

    public function saveProviderProperty(Provider $provider, Property $property, array $data)
    {
        $findProviderProperty = $this->findProviderProperty($provider, $property);
        if (!$findProviderProperty instanceof Property) {
            $create = ProviderProperty::create(['provider_id' => $provider->id, 'property_id' => $property->id, ...$data]);
            return $create->exists;
        }

        $update = $provider->properties()->updateExistingPivot($property->id, $data);
        return true;
    }

    public function deleteProviderProperty(Provider $provider, Property $property)
    {
        return ($provider->properties()->detach($property->id) > 0);
    }

}
