<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class ServiceRequestParameterCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'service_request_parameters';

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
