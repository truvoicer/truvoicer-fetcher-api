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
     */
    public function initialiseUserSettings(): UserSetting
    {
        /** @var \Truvoicer\TfDbReadCore\Models\UserSetting $settings */
        $settings = $this->user->settings()->create();

        return $settings;
    }

    /**
     * Update or create user settings for the current user.
     *
     * @param  array  $data  The settings data to update/create
     */
    public function updateUserSettings(array $data): UserSetting
    {
        /** @var \Truvoicer\TfDbReadCore\Models\UserSetting $settings */
        $settings = $this->user->settings()->updateOrCreate(
            [
                'user_id' => $this->user->id,
            ],
            $data
        );

        return $settings;
    }
}
