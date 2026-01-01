<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;

class PersonalAccessTokenCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'tokens';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
