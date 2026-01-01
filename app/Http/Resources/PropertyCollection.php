<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class PropertyCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public static $wrap = 'properties';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
