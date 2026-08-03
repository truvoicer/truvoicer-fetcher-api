<?php

namespace App\Http\Resources\User\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Truvoicer\TfDbReadCore\Models\UserSetting;

/**
 * @mixin UserSetting
 */
class UserSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'theme' => $this->theme,
            'open_mode' => $this->open_mode,
        ];
    }
}
