<?php

namespace App\Models;

use App\Repositories\ServiceUserPermissionRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceUserPermission extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'service_user_permissions';
    public const REPOSITORY = ServiceUserPermissionRepository::class;
    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class);
    }


    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
