<?php

namespace App\Paginators;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CollectionPaginator extends LengthAwarePaginator
{
    public function __construct(Collection $items, int $total, int $perPage, int $currentPage, array $options = [])
    {
        parent::__construct($items, $total, $perPage, $currentPage, $options);
        // slice the collection to the per page limit
        $items = $items->slice(($currentPage - 1) * $perPage, $perPage);
        $this->items = $items;
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items->toArray(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total' => $this->total(),
            'total_pages' => $this->lastPage(),
            'has_more' => $this->hasMorePages(),
        ];
    }
}
