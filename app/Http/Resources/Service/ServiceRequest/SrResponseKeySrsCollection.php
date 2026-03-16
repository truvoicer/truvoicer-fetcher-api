<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

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
