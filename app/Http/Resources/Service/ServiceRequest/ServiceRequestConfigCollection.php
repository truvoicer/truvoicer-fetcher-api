<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class ServiceRequestConfigCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'service_request_configs';

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
