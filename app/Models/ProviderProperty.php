<?php

namespace App\Models;

use App\Repositories\ProviderPropertyRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\EntityService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderProperty extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'provider_properties';
    public const REPOSITORY = ProviderPropertyRepository::class;

    protected $casts = [
        'array_value' => 'array'
    ];

    protected $fillable = [
        'provider_id',
        'property_id',
        'value',
        'array_value'
    ];

//    protected $with = ['provider', 'property'];
    protected function arrayValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->getArrayValue($value, $attributes, request()->user()),
        );
    }
    public function getArrayValue(mixed $value, array $attributes, User $user)
    {
        $value = json_decode($value, true);
        switch ($this->property->value_type) {
            case DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST:
                if (!is_array($value)) {
                    return $value;
                }
                if (!count($value)) {
                    return $value;
                }
                $data = [];
                foreach ($value as $key => $valueItem) {
                    foreach (EntityService::ENTITIES as $entity) {
                        if (!array_key_exists($entity, $data)) {
                            $data[$entity] = [];
                        }
                        if (empty($valueItem[$entity])) {
                            continue;
                        }
                        if (is_array($valueItem[$entity])) {
                            $data[$entity] = [
                                ...$data[$entity],
                                ...$valueItem[$entity],
                            ];
                            continue;
                        }
                        $data[$entity][] = $valueItem[$entity];
                    }
                }
                $data = array_filter($data, function ($item) {
                    return !empty($item);
                });
                $collection = [];
                foreach ($data as $key => $valueItem) {
                    $srs = EntityService::getInstance()->getEntityListByEntityIds($user, $key, $valueItem);
                    if ($srs instanceof Collection) {
                        $collection[$key] = $srs;
                    }
                }

                return array_map(function ($item) use ($collection) {
                    foreach (EntityService::ENTITIES as $entity) {
                        if (!array_key_exists($entity, $item)) {
                            continue;
                        }
                        if (!is_array($item[$entity])) {
                            continue;
                        }
                        $collectionItem = $collection[$entity];
                        $item[$entity] = array_map(function ($entityItem) use($collectionItem) {
                            return $collectionItem->firstWhere('id', $entityItem)->only(['id', 'name', 'label', 'type']);
                        }, $item[$entity]);

                    }
                    return $item;
                }, $value);
            default:
                return $value;
        }
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }
}
