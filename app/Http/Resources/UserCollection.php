<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'users';

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
