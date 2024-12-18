<?php

namespace App\Models;

use App\Enums\Import\EntityLockStatus;
use App\Repositories\CategoryRepository;
use App\Repositories\CategoryUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'status',
        'locked_at',
    ];

    protected $casts = [
        'status' => EntityLockStatus::class,
    ];

    protected $attributes = [
        'status' => EntityLockStatus::class,
    ];

    public function entityLockable(): MorphTo
    {
        return $this->morphTo();
    }

}
