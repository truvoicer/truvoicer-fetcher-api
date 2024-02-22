<?php

namespace App\Http\Resources;

use App\Helpers\Resources\ResourceHelpers;
use Illuminate\Http\Request;

class ApiSearchListResourceCollection extends BaseCollection
{

    public static $wrap = 'results';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = ResourceHelpers::buildCollectionResponseProperties($this->collection);
        $data[$this::$wrap] = ResourceHelpers::buildCollectionResults($this->collection);
        if ($this->hasPagination()) {
            $data['pagination'] = $this->buildLinks($this->resource);
        }
        return $data;
    }
}
