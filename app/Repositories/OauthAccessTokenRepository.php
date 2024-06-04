<?php

namespace App\Repositories;

use App\Models\OauthAccessToken;
use App\Models\Provider;
use App\Models\Sr;
use DateTime;

class OauthAccessTokenRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(OauthAccessToken::class);
    }

    public function getModel(): OauthAccessToken
    {
        return parent::getModel();
    }

    public function insertOathToken(Sr $sr, string $token, DateTime $expiry) {
        return $sr->oauthAccessToken()->create([
            'access_token' => $token,
            'expiry' => $expiry,
        ]);
    }


    public function getLatestAccessToken(Sr $sr) {
        $dateTime = now();
        return $sr->oauthAccessToken()
            ->whereDate('expiry', '>', $dateTime)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
