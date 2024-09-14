<?php

namespace App\Models;

use App\Repositories\ProviderPropertyRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\EntityService;
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

    public function getArrayValue(User $user)
    {
        switch ($this->property->value_type) {
            case DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST:
                if (!is_array($this->array_value)) {
                    return $this->array_value;
                }
                if (!count($this->array_value)) {
                    return $this->array_value;
                }
                $data = [];
                foreach ($this->array_value as $key => $value) {
                    foreach (EntityService::ENTITIES as $entity) {
                        if (!array_key_exists($entity, $data)) {
                            $data[$entity] = [];
                        }
                        if (empty($value[$entity])) {
                            continue;
                        }
                        if (is_array($value[$entity])) {
                            $data[$entity] = [
                                ...$data[$entity],
                                ...$value[$entity],
                            ];
                            continue;
                        }
                        $data[$entity][] = $value[$entity];
                    }
                }
                $data = array_filter($data, function ($item) {
                    return !empty($item);
                });
                $collection = [];
                foreach ($data as $key => $value) {
                    $srs = EntityService::getInstance()->getEntityListByEntityIds($user, $key, $value);
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
                }, $this->array_value);
            default:
                return $this->array_value;
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
}
