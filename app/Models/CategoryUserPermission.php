<?php

namespace App\Models;

use App\Repositories\CategoryUserPermissionRepository;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryUserPermission extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'category_user_permissions';
    public const REPOSITORY = CategoryUserPermissionRepository::class;

    public function categoryUser()
    {
        return $this->belongsTo(CategoryUser::class);
    }


    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
