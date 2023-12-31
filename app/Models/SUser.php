<?php

namespace App\Models;

use App\Repositories\SUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SUser extends Model
{
    use HasFactory;
    public const TABLE_NAME = 's_users';
    public const REPOSITORY = SUserRepository::class;

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            SUserPermission::TABLE_NAME,
            's_user_id',
            'permission_id'
        );
    }
    public function service()
    {
        return $this->belongsTo(
            S::class
        );
    }
    public function sUserPermission()
    {
        return $this->hasMany(
            SUserPermission::class
        );
    }
}
