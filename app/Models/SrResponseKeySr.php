<?php

namespace App\Models;

use App\Repositories\SrResponseKeySrRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SrResponseKeySr extends Pivot
{
    use HasFactory;
    public const TABLE_NAME = 'sr_response_key_srs';
    public const REPOSITORY = SrResponseKeySrRepository::class;
    protected $table = self::TABLE_NAME;
    protected $casts = [
        'request_response_keys' => 'array',
        'response_response_keys' => 'array'
    ];
    protected $fillable = [
        'sr_id',
        'sr_response_key_id',
        'request_response_keys',
        'response_response_keys',
    ];
}
