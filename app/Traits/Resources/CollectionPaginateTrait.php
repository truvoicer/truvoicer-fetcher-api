<?php

namespace App\Traits\Resources;

use Illuminate\Pagination\LengthAwarePaginator;

trait CollectionPaginateTrait
{
    public function buildLinks(LengthAwarePaginator $resource)
    {
        return [
            'totalPages' => ceil($resource->total() / $resource->perPage()),
            'total' => $resource->total(),
            'perPage' => $resource->perPage(),
            'prevPage' => $resource->currentPage() - 1,
            'currentPage' => $resource->currentPage(),
            'nextPage' => $resource->currentPage() + 1,
            'lastPage' => $resource->lastPage(),
        ];
    }
}
