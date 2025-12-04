<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProviderPropertyEntity extends Model
{
    protected $table = 'provider_property_entities';

    protected $fillable = [
        'provider_property_id',
        'entityable_id',
        'entityable_type',
    ];

    public function entityable(): MorphTo
    {
        return $this->morphTo();
    }

    public function providerProperty(): BelongsTo
    {
        return $this->belongsTo(ProviderProperty::class);
    }
}
