<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'categories';
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


}
