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
    public function srResponseKey()
    {
        return $this->hasMany(SrResponseKey::class);
    }
    public function srResponseKeys()
    {
        return $this->belongsToMany(
            SResponseKey::class,
            SrResponseKey::TABLE_NAME,
            'sr_id',
            's_response_key_id'
        );
    }
    public function service()
    {
        return $this->belongsTo(S::class);
    }
}
