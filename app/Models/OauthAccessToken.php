<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OauthAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
      'access_token',
      'expiry'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
