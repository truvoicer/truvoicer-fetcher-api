<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProvider extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'user_providers';
    public function permission()
    {
        return $this->hasMany(Permission::class);
    }
}
