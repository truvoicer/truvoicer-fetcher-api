<?php

namespace App\Models;

use App\Repositories\ServiceUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceUser extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'service_users';
    public const REPOSITORY = ServiceUserRepository::class;

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            ServiceUserPermission::TABLE_NAME,
            'service_user_id',
            'permission_id'
        );
    }
    public function service()
    {
        return $this->belongsTo(
            Service::class
        );
    }
    public function serviceUserPermission()
    {
        return $this->hasMany(
            ServiceUserPermission::class
        );
    }
}
