<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;
use App\Http\Resources\ProviderMinimalResource;
use App\Http\Resources\ProviderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
