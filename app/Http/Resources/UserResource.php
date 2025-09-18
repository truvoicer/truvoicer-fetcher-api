<?php

namespace App\Http\Resources;

use App\Http\Resources\User\Setting\UserSettingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "email" => $this->email,
            "email_verified_at" => $this->e,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "roles" => $this->whenLoaded(
                'roles',
                RoleResource::collection($this->roles)
            ),
            'settings' => $this->whenLoaded(
                'settings',
                new UserSettingResource(
                    $this->settings()->first()
                )
            ),
        ];
    }
}
