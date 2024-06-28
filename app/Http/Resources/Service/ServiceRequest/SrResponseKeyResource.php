<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SrResponseKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['provider'] = new ProviderMinimalResource($this->provider);
        $data['sr_response_key_srs'] = new SrResponseKeySrsCollection($this->whenLoaded('srResponseKeySrs'));
        return $data;
    }
}
