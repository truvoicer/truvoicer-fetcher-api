<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderPropertyResource extends JsonResource
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
            "provider_id" => $this->provider_id,
            "property_id" => $this->property_id,
            "value" => $this->value,
            "array_value" => $this->getArrayValue($request->user()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
