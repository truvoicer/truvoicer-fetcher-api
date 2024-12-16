<?php

namespace App\Models;

use App\Repositories\CategoryRepository;
use App\Repositories\CategoryUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityLock extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'categories';
    public const REPOSITORY = CategoryRepository::class;


    protected $fillable = [
        'user_id',
        'entity_type',
        'status',
        'locked_at',
        'unlocked_at',
    ];

    public function entityLockable(): MorphTo
    {
        return $this->morphTo();
    }

}
