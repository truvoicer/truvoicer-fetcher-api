<?php

namespace App\Models;

use App\Repositories\ProviderPropertyRepository;
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

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function property() {
        return $this->belongsTo(Property::class);
    }
}
