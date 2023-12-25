<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryUser extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'category_users';

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            CategoryUserPermission::TABLE_NAME,
            'category_user_id',
            'permission_id'
        );
    }
}
