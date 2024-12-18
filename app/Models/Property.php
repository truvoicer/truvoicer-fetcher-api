<?php

namespace App\Models;

use App\Repositories\PropertyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'properties';
    public const REPOSITORY = PropertyRepository::class;

    protected $casts = [
        'value_choices' => 'array',
        'entities' => 'array'
    ];
    protected $fillable = [
        'name',
        'label',
        'value_type',
        'value_choices',
        'entities'
    ];
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            ProviderProperty::TABLE_NAME,
            'property_id',
            'provider_id',
        );
    }

    public function providerProperty()
    {
        return $this->hasOne(
            ProviderProperty::class,
            'property_id',
            'id'
        );
    }

    public function srConfig()
    {
        return $this->hasOne(
            SrConfig::class,
            'property_id',
            'id'
        );
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }
}
