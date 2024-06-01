<?php

namespace App\Models;

use App\Repositories\ProviderPropertyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySrConfig extends Model
{
    use HasFactory; public const TABLE_NAME = 'property_sr_configs';
    public const REPOSITORY = ProviderPropertyRepository::class;

    protected $casts = [
        'array_value' => 'array'
    ];

    protected $fillable = [
        'sr_config_id',
        'property_id',
        'value',
        'array_value'
    ];

//    protected $with = ['provider', 'property'];

    public function srConfig()
    {
        return $this->belongsTo(SrConfig::class);
    }

    public function property() {
        return $this->belongsTo(Property::class);
    }
}
