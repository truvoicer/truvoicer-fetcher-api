<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;

class PropertyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Property::class);
    }

    public function getModel(): Property
    {
        return parent::getModel();
    }
    public function getAllPropertiesArray() {
        return $this->findAll();
    }

    public function getProviderPropertyByPropertyName(Provider $provider, string $propertyName) {
        return $provider->properties()
            ->where('name', $propertyName)
            ->whereHas('providerProperty', function ($query) use ($provider) {
                $query->where('provider_id', $provider->id);
            })
            ->with('providerProperty', function ($query) use ($provider) {
                $query->where('provider_id', $provider->id);
            })
            ->first();
    }
    public function createProperty(array $data) {
        return $this->insert($data);
    }

    public function updateProperty(Property $propertyObject, array $data) {
        $this->setModel($propertyObject);
        return $this->save($data);
    }


    public function deleteProperty(Property $property) {
        $this->setModel($property);
        return $this->delete();
    }
    public function findByParams(string $sort, string  $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }
}
