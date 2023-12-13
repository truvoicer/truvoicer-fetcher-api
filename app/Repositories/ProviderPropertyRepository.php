<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;

class ProviderPropertyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderProperty::class);
    }
    public function createProviderProperty(Provider $provider, Property $property, string $propertyValue) {
//        $providerProperty = new ProviderProperty();
//        $providerProperty->setProvider($provider);
//        $providerProperty->setProperty($property);
//        $providerProperty->setValue($propertyValue);
        return $provider->property()->save($property);
    }
    public function saveProviderProperty(ProviderProperty $providerProperty) {
        $this->setModel($providerProperty);
        return $this->save();
    }

    public function deleteProviderProperty(ProviderProperty $providerProperty) {
        $this->setModel($providerProperty);
        return $this->delete();
    }

}
