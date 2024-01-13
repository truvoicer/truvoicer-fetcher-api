<?php

namespace App\Models;

use App\Repositories\CategoryRepository;
use App\Repositories\CategoryUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'categories';
    public const REPOSITORY = CategoryRepository::class;
    public const RELATED_USER_REPOSITORY = CategoryUserRepository::class;
    private string $name;
    private string $label;
    protected $fillable = [
        'name',
        'label'
    ];
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            CategoryUser::TABLE_NAME,
            'category_id',
            'user_id'
        );
    }
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function permissions() {
        return $this->hasManyThrough(CategoryUserPermission::class, CategoryUser::class);
    }
    public function categoryUser()
    {
        return $this->hasMany(
            CategoryUser::class
        );
    }

    public function sr()
    {
        return $this->hasMany(
            Sr::class
        );
    }

}
