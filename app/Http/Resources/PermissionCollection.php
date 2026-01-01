<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Truvoicer\TruFetcherGet\Http\Resources\BaseCollection;

class PermissionCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'permissions';
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
