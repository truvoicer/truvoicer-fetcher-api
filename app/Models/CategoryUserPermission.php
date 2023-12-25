<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryUserPermission extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'category_user_permissions';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
