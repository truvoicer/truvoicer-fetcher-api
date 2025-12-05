<?php

namespace App\Models;

use App\Enums\Entity\EntityType;
use App\Repositories\SrConfigRepository;
use App\Services\ApiManager\Data\DataConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SrConfig extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'sr_configs';
    public const REPOSITORY = SrConfigRepository::class;

    protected $casts = [
        'array_value' => 'array',
    ];

    protected $fillable = [
        'sr_id',
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

                $this->srConfigEntities->each(
                    function (SrConfigEntity $srConfigEntity) use(&$data) {
                        $entityType = EntityType::fromClassName($srConfigEntity->entityable_type);
                        if (!$entityType) {
                            return;
                        }
                        $find = $this->findIndex($data, $entityType->value);
                        if ($find === false) {
                            $data[] = [
                                $entityType->value => [
                                    $srConfigEntity->entityable->only(['id', 'name', 'label'])
                                ]
                            ];
                        } else {
                            $data[$find][$entityType->value][] = $srConfigEntity->entityable->only(['id', 'name', 'label']);
                        }
                    }
                );
                return $data;
            default:
                return $value;
        }
    }

    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }

    public function property()
    {
        return $this->hasOne(
            Property::class,
            'id',
            'property_id'
        );
    }

    public function properties(): HasMany
    {
        return $this->hasMany(
            Property::class,
        );
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }

    public function srConfigEntities(): HasMany
    {
        return $this->hasMany(
            SrConfigEntity::class,
        );
    }

}
