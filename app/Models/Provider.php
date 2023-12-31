<?php

namespace App\Models;

use App\Repositories\ProviderRepository;
use App\Repositories\ProviderUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'providers';
    public const REPOSITORY = ProviderRepository::class;
    public const RELATED_USER_REPOSITORY = ProviderUserRepository::class;

    protected $fillable = [
        'name',
        'label',
        'api_base_url',
        'access_key',
        'secret_key',
        'user_id',
    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            ProviderUser::TABLE_NAME,
            'provider_id',
            'user_id'
        );
    }
    public function permissions() {
        return $this->hasManyThrough(ProviderUserPermission::class, ProviderUser::class);
    }

    public function providerUser()
    {
        return $this->hasMany(
            ProviderUser::class
        );
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->hasMany(Category::class);
    }
    public function serviceRequest()
    {
        return $this->hasMany(Sr::class);
    }
    public function property()
    {
        return $this->hasMany(Property::class);
    }
    public function properties()
    {
        return $this->belongsToMany(
            Property::class,
            ProviderProperty::TABLE_NAME,
            'property_id',
            'provider_id'
        );
    }
    public function oauthAccessToken()
    {
        return $this->hasMany(OauthAccessToken::class);
    }
}
