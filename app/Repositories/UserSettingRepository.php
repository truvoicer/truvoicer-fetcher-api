<?php

namespace App\Repositories;

use Truvoicer\TfDbReadCore\Models\UserSetting;
use Truvoicer\TfDbReadCore\Repositories\BaseRepository;

class UserSettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserSetting::class);
    }

}
