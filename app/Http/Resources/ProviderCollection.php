<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class ProviderCollection extends BaseCollection
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
