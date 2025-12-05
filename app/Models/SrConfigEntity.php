<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SrConfigEntity extends Model
{
    protected $table = 'sr_config_entities';

    protected $fillable = [
        'sr_config_id',
        'entityable_id',
        'entityable_type',
    ];

    public function entityable(): MorphTo
    {
        return $this->morphTo();
    }

    public function srConfig(): BelongsTo
    {
        return $this->belongsTo(SrConfig::class);
    }
}
