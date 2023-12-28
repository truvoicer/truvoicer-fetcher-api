<?php

namespace App\Models;

use App\Repositories\RoleUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'role_users';
    public const REPOSITORY = RoleUserRepository::class;
}
