<?php

namespace App\Models;

use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'roles';
    public const REPOSITORY = RoleRepository::class;

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
