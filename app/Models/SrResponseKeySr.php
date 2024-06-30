<?php

namespace App\Models;

use App\Repositories\SrResponseKeySrRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SrResponseKeySr extends Pivot
{
    use HasFactory;
    public const TABLE_NAME = 'sr_response_key_srs';
    public const REPOSITORY = SrResponseKeySrRepository::class;
    protected $table = self::TABLE_NAME;
    protected $casts = [
        'response_keys' => 'array'
    ];
    protected $fillable = [
        'sr_id',
        'sr_response_key_id',
        'response_keys'
    ];
}
