<?php

namespace App\Http\Resources\Service;

use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ServiceCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'services';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
