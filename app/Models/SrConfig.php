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
        'name',
        'value',
        'value_type',
        'array_value',
    ];
    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }
}
