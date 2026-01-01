<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SrResponseKeySrsCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public static $wrap = 'sr_response_key_srs';
    public $collects = SrResponseKeySrsResource::class;
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
