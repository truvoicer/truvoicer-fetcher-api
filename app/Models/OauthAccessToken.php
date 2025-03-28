<?php

namespace App\Models;

use App\Repositories\OauthAccessTokenRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OauthAccessToken extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'oauth_access_tokens';
    public const REPOSITORY = OauthAccessTokenRepository::class;

    protected $fillable = [
      'access_token',
      'expiry'
    ];

    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }
}
