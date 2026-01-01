<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;

class CategoryCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public static $wrap = 'categories';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
