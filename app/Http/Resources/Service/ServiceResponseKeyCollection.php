<?php

namespace App\Http\Resources\Service;

use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ServiceResponseKeyCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'service_response_keys';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
