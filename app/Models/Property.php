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
    ];
    protected $fillable = [
        'name',
        'label',
        'value_type',
        'value_choices',
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
            'provider_id',
            'property_id'
        );
    }

    public function providerProperty()
    {
        return $this->hasMany(
            ProviderProperty::class
        );
    }
}
