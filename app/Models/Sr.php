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
    protected $with = ['childSrs'];
    protected $fillable = [
        'name',
        'label',
        'pagination_type',
        'query_parameters',
        'type',
        'default_sr',
        'default_data',
    ];
    protected $casts = [
        'pagination_type' => 'json',
        'query_parameters' => 'json',
        'default_sr' => 'boolean',
        'default_data' => 'json',
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

    public function srRateLimit()
    {
        return $this->hasOne(SrRateLimit::class, 'sr_id', 'id');
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
            SrResponseKey::class,
            SrResponseKeySr::TABLE_NAME,
            'sr_id',
            'sr_response_key_id'
        )
            ->withPivot('response_response_keys', 'request_response_keys', 'action', 'single_request', 'disable_request')
            ->using(SrResponseKeySr::class);
    }

    public function srChildSr()
    {
        return $this->hasOne(
            SrChildSr::class,
            'sr_child_id',
            'id'
        )->with('parentSr');
    }
    public function parentSrs()
    {
        return $this->belongsToMany(
            self::class,
            SrChildSr::TABLE_NAME,
            'sr_child_id',
            'sr_id'
        )->withPivot(
            'response_key_override',
            'config_override',
            'parameter_override',
            'scheduler_override',
            'rate_limits_override'
        );
    }

    public function childSrs()
    {
        return $this->belongsToMany(
            self::class,
            SrChildSr::TABLE_NAME,
            'sr_id',
            'sr_child_id'
        )->withPivot(
            'response_key_override',
            'config_override',
            'parameter_override',
            'scheduler_override',
            'rate_limits_override'
        );
    }

    public function oauthAccessToken()
    {
        return $this->hasMany(OauthAccessToken::class);
    }
}
