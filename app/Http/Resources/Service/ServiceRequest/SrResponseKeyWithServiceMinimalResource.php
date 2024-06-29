<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SrResponseKeyWithServiceMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sr_response_key' => new SrResponseKeyMinimalResource($this->srResponseKey),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
