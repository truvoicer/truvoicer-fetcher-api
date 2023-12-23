<?php

namespace App\Repositories;

use App\Models\OauthAccessToken;
use App\Models\Provider;
use App\Models\User;
use DateTime;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\NewAccessToken;

class PersonalAccessTokenRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(PersonalAccessToken::class);
    }

    public function getLatestAccessToken(User $user) {
        $dateTime = new DateTime();

        $token = $user->tokens()
            ->where('expires_at', '>', $dateTime->format('Y-m-d H:i:s'))
            ->orderBy('created_at', 'desc')
            ->first();
        if (!$token instanceof PersonalAccessToken) {
            return null;
        }
        return $token;
    }
}
