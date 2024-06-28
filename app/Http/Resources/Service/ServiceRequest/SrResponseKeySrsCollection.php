<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SrResponseKeySrsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public $collects = SrResponseKeySrsResource::class;
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
