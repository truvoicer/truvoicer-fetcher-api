<?php

namespace App\Repositories;

use App\Enums\Entity\EntityType;
use App\Models\Property;
use App\Models\PropertySrConfig;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\User;
use App\Services\EntityService;
use Exception;
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
        $this->setModel($create);
        return $create->exists;
    }


    public function saveSrConfigEntity(
        User $user,
        Sr $sr,
        Property $property,
        array $data
    ) {
        $findSrConfig = $this->findSrConfigProperty($sr, $property);
        if (!$findSrConfig instanceof Property) {

            $findSrConfig = SrConfig::create(['sr_id' => $sr->id, 'property_id' => $property->id, ...$data]);
            if (!$findSrConfig->exists()) {
                return false;
            }
        } else {
            $findSrConfig = $findSrConfig->srConfig;
        }
        $this->setModel($findSrConfig);

        foreach ($data as $index => $item) {
            foreach ($item as $entity => $ids) {
                $entityType = EntityType::tryFrom($entity);
                if (!$entityType) {
                    throw new Exception('Invalid entity type: ' . $entity);
                }
                if (!is_array($ids)) {
                    continue;
                }

                EntityService::getInstance()
                    ->syncSrConfigEntities(
                        $user,
                        $findSrConfig,
                        $entityType,
                        $ids
                    );
            }
        }
        return true;
    }

    public function deleteSrConfigProperty(Sr $sr, Property $property): int
    {
        return $sr->srConfig()
            ->where('property_id', '=', $property->id)
            ->delete();
    }
}
