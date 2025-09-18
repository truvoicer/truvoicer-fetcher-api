<?php

namespace App\Repositories;

use App\Models\UserSetting;

class UserSettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserSetting::class);
    }

}
