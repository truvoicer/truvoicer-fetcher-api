<?php

namespace App\Models;

use Truvoicer\TfDbReadCore\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Truvoicer\TfDbReadCore\Models\Category;
use Truvoicer\TfDbReadCore\Models\CategoryUser;
use Truvoicer\TfDbReadCore\Models\CategoryUserPermission;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\ProviderPropertyEntity;
use Truvoicer\TfDbReadCore\Models\ProviderUser;
use Truvoicer\TfDbReadCore\Models\ProviderUserPermission;
use Truvoicer\TfDbReadCore\Models\Role;
use Truvoicer\TfDbReadCore\Models\RoleUser;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Models\SrConfigEntity;
use Truvoicer\TfDbReadCore\Models\SUser;
use Truvoicer\TfDbReadCore\Models\SUserPermission;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const TABLE_NAME = 'users';
    public const REPOSITORY = UserRepository::class;

    protected $with = ['roles'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            RoleUser::TABLE_NAME,
            'user_id',
            'role_id'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            CategoryUser::class,
            'user_id',
            'category_id'
        );
    }
    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            ProviderUser::class,
            'user_id',
            'provider_id'
        );
    }
    public function s()
    {
        return $this->belongsToMany(
            S::class,
            SUser::class,
            'user_id',
            's_id'
        );
    }

    public function provider()
    {
        return $this->hasMany(Provider::class);
    }
    public function categoryPermissions() {
        return $this->hasManyThrough(CategoryUserPermission::class, CategoryUser::class);
    }
    public function providerPermissions() {
        return $this->hasManyThrough(ProviderUserPermission::class, ProviderUser::class);
    }
    public function sPermissions() {
        return $this->hasManyThrough(SUserPermission::class, SUser::class);
    }

    public function settings(): HasOne {
        return $this->hasOne(UserSetting::class);
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
