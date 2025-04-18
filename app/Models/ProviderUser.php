<?php

namespace App\Models;

use App\Repositories\ProviderUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderUser extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'provider_users';
    public const REPOSITORY = ProviderUserRepository::class;

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            ProviderUserPermission::TABLE_NAME,
            'provider_user_id',
            'permission_id'
        );
    }
    public function provider()
    {
        return $this->belongsTo(
            Provider::class
        );
    }
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }
    public function providerUserPermission()
    {
        return $this->hasMany(
            ProviderUserPermission::class
        );
    }
}
