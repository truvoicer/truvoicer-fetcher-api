<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        if(!empty($data['accessToken']['expires_at'])) {
            $data['accessToken']['expires_at_timestamp'] = strtotime($data['accessToken']['expires_at']);
        }
        return $data;
    }
}
