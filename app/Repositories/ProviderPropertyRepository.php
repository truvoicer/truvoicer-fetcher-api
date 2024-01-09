<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProviderPropertyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderProperty::class);
    }

    public function getModel(): ProviderProperty
    {
        return parent::getModel();
    }

    public function findProviderProperties(Provider $provider)
    {
        $property = new Property();
        return $property->with(['providerProperty' => function (HasMany $query) use ($provider) {
            $query->where('provider_id', '=', $provider->id);
        }])
            ->get();
    }

    public function findProviderPropertyByProperty(Provider $provider, Property $property)
    {
        return $provider->properties()
            ->where('property_id', '=', $property->id)
            ->with('providerProperty')
            ->first();
    }

    public function saveProviderProperty(Provider $provider, Property $property, string $value)
    {
        $findProviderProperty = $this->findProviderPropertyByProperty($provider, $property);
        if (!$findProviderProperty instanceof Property) {
            return $this->dbHelpers->validateToggle(
                $provider->properties()->toggle([$property->id => ['value' => $value]]),
                [$property->id]
            );
        }

        $update = $provider->properties()->updateExistingPivot($property->id, ['value' => $value]);
        return true;
    }

    public function deleteProviderProperty(Provider $provider, Property $property)
    {
        return ($provider->properties()->detach($property->id) > 0);
    }

}
