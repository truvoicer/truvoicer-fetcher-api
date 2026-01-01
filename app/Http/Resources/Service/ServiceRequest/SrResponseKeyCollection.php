<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class SrResponseKeyCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public static $wrap = 'service_request_response_keys';
    public $collects = SrResponseKeyResource::class;
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
