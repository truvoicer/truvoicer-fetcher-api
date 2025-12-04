<?php

namespace App\Models;

use App\Repositories\SrConfigRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'value',
        'big_text_value',
        'array_value',
    ];

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

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }

    public function providerPropertyEntities(): MorphMany
    {
        return $this->morphMany(
            ProviderPropertyEntity::class,
            'entityable'
        );
    }

}
