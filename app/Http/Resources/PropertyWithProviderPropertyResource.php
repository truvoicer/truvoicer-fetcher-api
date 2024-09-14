<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyWithProviderPropertyResource extends JsonResource
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
            'label' => $this->label,
            'value_type' => $this->value_type,
            'value_choices' => $this->value_choices,
            'entities' => $this->entities,
            'provider_property' => new ProviderPropertyResource($this->whenLoaded('providerProperty')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
