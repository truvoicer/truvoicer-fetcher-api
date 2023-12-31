<?php

namespace App\Models;

use App\Repositories\SUserPermissionRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SUserPermission extends Model
{
    use HasFactory;
    public const TABLE_NAME = 's_user_permissions';
    public const REPOSITORY = SUserPermissionRepository::class;
    public function sUser()
    {
        return $this->belongsTo(SUser::class);
    }


    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
