<?php

namespace App\Services\User;

use App\Models\UserSetting;
use App\Repositories\UserSettingRepository;
use Truvoicer\TfDbReadCore\Services\BaseService;

class UserSettingService extends BaseService
{

    public function __construct(
        private UserSettingRepository $userSettingRepository
    )
    {
        parent::__construct();
    }

    public function findUserSettings() {
        $findSettings = $this->user->settings()->first();
        if (!$findSettings) {
            return $this->initialiseUserSettings();
        }
        return $findSettings;
    }

    public function initialiseUserSettings(): UserSetting {
        return $this->user->settings()->create();
    }

    public function updateUserSettings(array $data): UserSetting {
        return $this->user->settings()->updateOrCreate(
            [
                'user_id' => $this->user->id
            ],
            $data
        );
    }

}
