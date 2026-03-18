<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Truvoicer\TfDbReadCore\Models\Sr
 */
class SrTreeViewResource extends JsonResource
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
            'label' => $this->label,
            'name' => $this->name,
            'type' => $this->type,
            'provider' => $this->whenLoaded(
                'provider',
                new ProviderMinimalResource($this->provider)
            ),
            'hasChildren' => $this->childSrs->count() > 0,
            'children' => SrTreeViewResource::collection($this->childSrs),
        ];
    }
}
