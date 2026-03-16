<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

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
