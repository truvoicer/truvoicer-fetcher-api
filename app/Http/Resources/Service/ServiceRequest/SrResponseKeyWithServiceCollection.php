<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SrResponseKeyWithServiceCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public static $wrap = 'service_request_response_keys';
    public $collects = SrResponseKeyWithServiceResource::class;
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
