<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use App\Http\Resources\Service\SResponseKeyMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SrResponseKey
 */
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
        $data['s_response_key'] = $this->whenLoaded(
            'sResponseKey',
            new SResponseKeyMinimalResource($this->sResponseKey)
        );
        $data['provider'] = new ProviderMinimalResource($this->provider);
        return $data;
    }
}
