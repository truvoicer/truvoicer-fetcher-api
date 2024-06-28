<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SrResponseKeyWithServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['sr_response_key'] = new SrResponseKeyResource($this->whenLoaded('srResponseKey'));
        return $data;
    }
}
