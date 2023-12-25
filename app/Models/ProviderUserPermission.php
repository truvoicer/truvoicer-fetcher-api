<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderUserPermission extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'provider_user_permissions';
}
