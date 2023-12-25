<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'roles';

    protected $fillable = [
        'name',
        'label',
        'ability'
    ];
    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            RoleUser::TABLE_NAME,
            'role_id',
            'user_id'
        );
    }
}
