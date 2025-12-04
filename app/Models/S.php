<?php

namespace App\Models;

use App\Repositories\ProviderUserRepository;
use App\Repositories\SRepository;
use App\Repositories\SUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class S extends Model
{
    use HasFactory;
    public const TABLE_NAME = 's';
    public const REPOSITORY = SRepository::class;
    public const RELATED_USER_REPOSITORY = SUserRepository::class;
    protected $fillable = [
        'name',
        'label'
    ];
    public function sr(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sr::class);
    }
    public function sResponseKeys()
    {
        return $this->hasMany(SResponseKey::class);
    }
    public function sResponseKey()
    {
        return $this->hasMany(SResponseKey::class);
    }
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            SUser::TABLE_NAME,
            's_id',
            'user_id'
        );
    }
    public function permissions() {
        return $this->hasManyThrough(SUserPermission::class, SUser::class);
    }
    public function sUser()
    {
        return $this->hasMany(
            SUser::class
        );
    }
    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            Sr::class,
            's_id',
            'provider_id'
        );
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
}
