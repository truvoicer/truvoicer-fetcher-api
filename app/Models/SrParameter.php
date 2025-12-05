<?php

namespace App\Models;

use App\Repositories\SrParameterRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SrParameter extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'sr_parameters';
    public const REPOSITORY = SrParameterRepository::class;

    protected $fillable = [
        'name',
        'value',
    ];
    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }

    public function providerPropertyEntities(): MorphMany
    {
        return $this->morphMany(
            ProviderPropertyEntity::class,
            'entityable'
        );
    }

    public function srConfigEntities(): MorphMany
    {
        return $this->morphMany(
            SrConfigEntity::class,
            'entityable'
        );
    }
}
