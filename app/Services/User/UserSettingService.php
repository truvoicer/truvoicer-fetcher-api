<?php

namespace App\Services\User;

use Truvoicer\TfDbReadCore\Models\UserSetting;
use Truvoicer\TfDbReadCore\Services\BaseService;

class UserSettingService extends BaseService
{

    public function findUserSettings()
    {
        $findSettings = $this->user->settings()->first();
        if (! $findSettings) {
            return $this->initialiseUserSettings();
        }

        return $findSettings;
    }

    /**
     * Initialize user settings for the current user.
     *
     * @return \Truvoicer\TfDbReadCore\Models\UserSetting
     */
    public function initialiseUserSettings(): UserSetting
    {
        return $this->user->settings()->create();
    }

    /**
     * Update or create user settings for the current user.
     *
     * @param array $data The settings data to update/create
     * @return \Truvoicer\TfDbReadCore\Models\UserSetting
     */
    public function updateUserSettings(array $data): UserSetting
    {
        return $this->user->settings()->updateOrCreate(
            [
                'user_id' => $this->user->id,
            ],
            $data
        );
    }
}
