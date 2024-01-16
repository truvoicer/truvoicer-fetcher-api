<?php

namespace App\Models;

use App\Repositories\SResponseKeyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SResponseKey extends Model
{
    use HasFactory;

    public const TABLE_NAME = 's_response_keys';
    public const REPOSITORY = SResponseKeyRepository::class;
    protected $fillable = [
        'name',
    ];

    public function srResponseKeys()
    {
        return $this->belongsToMany(
            Sr::class,
            SrResponseKey::TABLE_NAME,
            's_response_key_id',
            'sr_id'
        );
    }

    public function srResponseKey()
    {
        return $this->hasOne(
            SrResponseKey::class,
            's_response_key_id',
            'id'
        );
    }
    public function service()
    {
        return $this->belongsTo(S::class);
    }
}
