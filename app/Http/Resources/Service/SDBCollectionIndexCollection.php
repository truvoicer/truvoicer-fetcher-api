<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class SDBCollectionIndexCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'indexes';

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
