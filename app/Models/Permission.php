<?php

namespace App\Models;

use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'permissions';
    public const REPOSITORY = PermissionRepository::class;

    public function categoryUsers()
    {
        return $this->belongsToMany(
            CategoryUser::class,
            CategoryUserPermission::TABLE_NAME,
            'permission_id',
            'category_user_id'
        );
    }

    public function providerUsers()
    {
        return $this->belongsToMany(
            ProviderUser::class,
            ProviderUserPermission::TABLE_NAME,
            'permission_id',
            'provider_user_id',
        );
    }
    public function categoryPermissions() {
        return $this->hasManyThrough(CategoryUserPermission::class, CategoryUser::class);
    }
}
