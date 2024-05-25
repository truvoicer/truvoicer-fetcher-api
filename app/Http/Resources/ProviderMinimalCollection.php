<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProviderMinimalCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'providers';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
