<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\BaseCollection;
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
            'name' => $this->name,
            'type' => $this->type,
            'hasChildren' => $this->childSrs->count() > 0,
            'children' => SrTreeViewResource::collection($this->childSrs),
        ];
    }
}
