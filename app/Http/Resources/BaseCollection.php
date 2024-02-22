<?php

namespace App\Http\Resources;

use App\Traits\Resources\CollectionPaginateTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class BaseCollection extends ResourceCollection
{
    use CollectionPaginateTrait;

    protected function hasPagination(): bool
    {
        return (
            $this->resource instanceof AbstractPaginator
        );
    }
    public function toArray(Request $request): array
    {
        $data =  [
            $this::$wrap => $this->collection,
        ];
        if ($this->hasPagination()) {
            $data['pagination'] = $this->buildLinks($this->resource);
        }
        return $data;
    }
}
