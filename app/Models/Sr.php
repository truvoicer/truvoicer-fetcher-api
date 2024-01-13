<?php

namespace App\Models;

use App\Repositories\SrRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sr extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'srs';
    public const REPOSITORY = SrRepository::class;
    protected $with = ['category', 's'];
    protected $fillable = [
        'name',
        'label',
        'pagination_type',
    ];
    public function s()
    {
        return $this->belongsTo(S::class);
    }
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function srConfig()
    {
        return $this->hasMany(SrConfig::class);
    }
    public function srParameter()
    {
        return $this->hasMany(SrParameter::class);
    }
    public function srResponseKey()
    {
        return $this->hasMany(SrResponseKey::class);
    }
    public function srResponseKeys()
    {
        return $this->belongsToMany(
            Sr::class,
            SrResponseKey::TABLE_NAME,
            's_response_key_id',
            'sr_id'
        );
    }
    public function srResponseKeySrs()
    {
        return $this->belongsToMany(
            Sr::class,
            SrResponseKeySr::TABLE_NAME,
            'sr_response_key_id',
            'sr_id'
        );
    }

}
