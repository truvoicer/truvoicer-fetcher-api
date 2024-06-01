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
            ->with(['propertySrConfig' => function (HasOne $query) use ($serviceRequest) {
                $query->whereHas('srConfig', function ($query) use ($serviceRequest) {
                    $query->whereHas('sr', function ($query) use ($serviceRequest) {
                        $query->where('id', '=', $serviceRequest->id);

                    });
                });
            }]);
        return $this->getResults($query);
    }

    public function getRequestConfigByName(Sr $serviceRequest, string $configItemName)
    {
        return $serviceRequest
            ->srConfig()
            ->where('name', $configItemName)
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
            ->whereHas('propertySrConfig',  function (HasOne $query) use ($property) {
                $query->where('property_id', '=', $property->id);
            })
            ->first();
    }

    public function saveSrConfigProperty(Sr $sr, Property $property, array $data)
    {
        $findSrConfigProperty = $this->findSrConfigProperty($sr, $property);
        if (!$findSrConfigProperty instanceof SrConfig) {

            return $create->exists;
        }

        $update = $findSrConfigProperty->properties()->updateExistingPivot($property->id, $data);
        return true;
    }

    public function deleteSrConfigProperty(SrConfig $srConfig, Property $property)
    {
        return ($srConfig->properties()->detach($property->id) > 0);
    }
}
