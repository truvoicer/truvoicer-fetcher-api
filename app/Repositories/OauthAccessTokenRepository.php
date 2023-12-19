<?php

namespace App\Repositories;

use App\Models\OauthAccessToken;
use App\Models\Provider;
use DateTime;

class OauthAccessTokenRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(OauthAccessToken::class);
    }

    public function insertOathToken(string $token, DateTime $expiry, Provider $provider) {
        return $this->save([
            'access_token' => $token,
            'expiry' => $expiry,
            'provider' => $provider->id
        ]);
    }


    public function getLatestAccessToken(Provider $provider) {
        $dateTime = new DateTime();
        return $provider->oauthAccessToken()
            ->where('expiry', '>', $dateTime->format('Y-m-d H:i:s'))
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
