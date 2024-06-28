<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
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
        $data['pivot'] = $this->pivot;
        $data['hasChildren'] = $this->childSrs->count() > 0;
        return $data;
    }
}
