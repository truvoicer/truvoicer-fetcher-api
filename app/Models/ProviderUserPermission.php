<?php

namespace App\Models;

use App\Repositories\ProviderUserPermissionRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderUserPermission extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'provider_user_permissions';
    public const REPOSITORY = ProviderUserPermissionRepository::class;
    public function providerUser()
    {
        return $this->belongsTo(ProviderUser::class);
    }


    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
