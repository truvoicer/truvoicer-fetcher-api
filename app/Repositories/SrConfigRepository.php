<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\PropertySrConfig;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\Sr;
use App\Models\SrConfig;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SrConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrConfig::class);
    }

    public function getModel(): SrConfig
    {
        return parent::getModel();
    }
    public function findBySr(Sr $serviceRequest)
    {
        return $this->getResults($serviceRequest->srConfig());
    }

    public function findByParams(Sr $serviceRequest, array $properties)
    {
//        return $serviceRequest->srConfig()
//            ->orderBy($sort, $order)
//            ->get();

        $property = new Property();
        $query = $property->query()
            ->whereIn('name', $properties)
            ->with(['srConfig' => function ($query) use ($serviceRequest) {
                $query->whereHas('sr', function ($query) use ($serviceRequest) {
                    $query->where('id', '=', $serviceRequest->id);

                });
            }]);
        return $this->getResults($query);
    }

    public function getRequestConfigByName(Sr $serviceRequest, string $propertyName)
    {
        return $serviceRequest->srConfig()
            ->whereHas('property', function ($query) use ($propertyName) {
                $query->where('name', '=', $propertyName);
            })
            ->first();
    }

    public function createRequestConfig(Sr $serviceRequest, array $data)
    {
        $create = $serviceRequest->srConfig()->create($data);
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }


    public function findSrConfigProperty(Sr $sr, Property $property)
    {
        return $sr->srConfig()
                ->where('property_id', '=', $property->id)
            ->first();
    }

    public function saveSrConfigProperty(Sr $sr, Property $property, array $data)
    {
        $findSrConfigProperty = $this->findSrConfigProperty($sr, $property);
        if (!$findSrConfigProperty instanceof SrConfig) {
            $create = $sr->srConfig()->create(['property_id' => $property->id, ...$data]);
            return $create->exists;
        }

        return $findSrConfigProperty->update( $data);
    }

}
