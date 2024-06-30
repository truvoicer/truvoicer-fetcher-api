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
        $property = new Property();
        $query = $property->query()
            ->whereHas('srConfig', function ($query) use ($serviceRequest) {
                $query->whereHas('sr', function ($query) use ($serviceRequest) {
                    $query->where('id', '=', $serviceRequest->id);
                });
            })
            ->with(['srConfig' => function ($query) use ($serviceRequest) {
                $query->whereHas('sr', function ($query) use ($serviceRequest) {
                    $query->where('id', '=', $serviceRequest->id);
                });
            }]);
        return $this->getResults($query);
    }

    public function findByParams(Sr $serviceRequest, array $properties)
    {
        $property = new Property();
        $query = $property->query()
            ->whereIn('name', $properties)
            ->whereHas('srConfig', function ($query) use ($serviceRequest) {
                $query->whereHas('sr', function ($query) use ($serviceRequest) {
                    $query->where('id', '=', $serviceRequest->id);
                });
            })
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

    public function findSrConfigProperty(Sr $sr, Property $property)
    {
        return $property->with(['srConfig' => function (HasOne $query) use ($sr) {
            $query->where('sr_id', '=', $sr->id);
        }])
            ->where('id', '=', $property->id)
            ->first();
    }

    public function saveSrConfigProperty(Sr $sr, Property $property, array $data)
    {
        $create = $property->srConfig()->updateOrCreate(
            [
                'property_id' => $property->id,
                'sr_id' => $sr->id
            ],
            [
                'sr_id' => $sr->id,
                ...$data
            ]
        );
        return $create->exists;
    }

    public function deleteSrConfigProperty(Sr $sr, Property $property): int
    {
        return $sr->srConfig()
            ->where('property_id', '=', $property->id)
            ->delete();
    }

}
