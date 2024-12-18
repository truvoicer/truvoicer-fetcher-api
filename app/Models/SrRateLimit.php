<?php

namespace App\Models;

use App\Repositories\SrRateLimitRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrRateLimit extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'sr_rate_limit';
    public const REPOSITORY = SrRateLimitRepository::class;
    public const FIELDS = [
        'override',
        'max_attempts',
        'decay_seconds',
        'delay_seconds_per_request',
    ];
    protected $fillable = self::FIELDS;

    public function sr()
    {
        return $this->belongsTo(Sr::class, 'sr_id', 'id');
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }
}
