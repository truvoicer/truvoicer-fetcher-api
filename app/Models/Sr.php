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
    protected $with = ['category', 's', 'srSchedule'];
    protected $fillable = [
        'name',
        'label',
        'pagination_type',
    ];
    protected $casts = [
        'pagination_type' => 'json',
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

    public function srSchedule()
    {
        return $this->hasOne(SrSchedule::class, 'sr_id', 'id');
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
    public function srResponseKeySrs()
    {
        return $this->belongsToMany(
            Sr::class,
            SrResponseKeySr::TABLE_NAME,
            'sr_id',
            'sr_response_key_id'
        );
    }

}
