<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderUser extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'provider_users';

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            ProviderUserPermission::TABLE_NAME,
            'provider_user_id',
            'permission_id'
        );
    }
}
