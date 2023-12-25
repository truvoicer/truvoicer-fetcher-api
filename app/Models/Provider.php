<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'providers';

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
        return $this->hasMany(ServiceRequest::class);
    }
    public function property()
    {
        return $this->hasMany(Property::class);
    }
    public function oauthAccessToken()
    {
        return $this->hasMany(OauthAccessToken::class);
    }
}
