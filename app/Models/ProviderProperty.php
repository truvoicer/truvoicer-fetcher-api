<?php

namespace App\Models;

use App\Enums\Entity\EntityType;
use App\Repositories\ProviderPropertyRepository;
use App\Services\ApiManager\Data\DataConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'big_text_value',
        'array_value',
    ];

    protected function arrayValue(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->getArrayValue($value, $attributes, request()->user()),
        );
    }
    public function findIndex(array $array, string $key) {
        foreach ($array as $index => $item) {
            if (array_key_exists($key, $item)) {
                return $index;
            }
        }
        return false;
    }
    public function getArrayValue(mixed $value, array $attributes, User $user)
    {
        $value = json_decode($value, true);
        switch ($this->property->value_type) {
            case DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST:

                $data = [];

                $this->providerPropertyEntities->each(
                    function (ProviderPropertyEntity $providerPropertyEntity) use(&$data) {
                        $entityType = EntityType::fromClassName($providerPropertyEntity->entityable_type);
                        if (!$entityType) {
                            return;
                        }
                        $find = $this->findIndex($data, $entityType->value);
                        if ($find === false) {
                            $data[] = [
                                $entityType->value => [
                                    $providerPropertyEntity->entityable->only(['id', 'name', 'label'])
                                ]
                            ];
                        } else {
                            $data[$find][$entityType->value][] = $providerPropertyEntity->entityable->only(['id', 'name', 'label']);
                        }
                    }
                );
                return $data;
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

    public function providerPropertyEntities(): HasMany
    {
        return $this->hasMany(ProviderPropertyEntity::class);
    }
}
