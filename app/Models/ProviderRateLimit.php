<?php

namespace App\Models;

use App\Repositories\ProviderRateLimitRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderRateLimit extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'provider_rate_limit';
    public const REPOSITORY = ProviderRateLimitRepository::class;
    public const FIELDS = [
        'max_attempts',
        'decay_seconds',
        'delay_seconds_per_request',
    ];
    protected $fillable = self::FIELDS;

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id', 'id');
    }
}
