<?php

namespace App\Http\Resources;

use App\Helpers\Resources\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiSearchItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = ResourceHelpers::buildCollectionResults((array)$this->resource);
        return $data;
    }
}
