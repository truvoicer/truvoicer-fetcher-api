<?php

namespace App\Http\Resources;

use App\Helpers\Resources\ResourceHelpers;
use App\Traits\Resources\CollectionPaginateTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiMongoDbSearchListCollection extends ResourceCollection
{
    use CollectionPaginateTrait;

    public static $wrap = 'results';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        $data[$this::$wrap] = ApiSearchItemResource::collection($this->collection);

        if ($this->hasPagination()) {
            $data['pagination'] = $this->buildLinks($this->resource);
        }
        return $data;
    }
}
