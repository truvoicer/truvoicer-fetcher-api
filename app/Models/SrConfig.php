<?php

namespace App\Models;

use App\Repositories\SrConfigRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrConfig extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'sr_configs';
    public const REPOSITORY = SrConfigRepository::class;

    protected $casts = [
        'value_choices' => 'array',
        'array_value' => 'array',
    ];

    protected $fillable = [
        'sr_id',
        'value',
        'value_type',
        'array_value',
        'value_choices',
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

}
