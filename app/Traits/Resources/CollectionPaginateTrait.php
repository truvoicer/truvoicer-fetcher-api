<?php

namespace App\Traits\Resources;

use App\Library\Defaults\DefaultData;
use Illuminate\Pagination\LengthAwarePaginator;

trait CollectionPaginateTrait
{
    public function buildLinks(LengthAwarePaginator $resource)
    {
        return [
            DefaultData::TOTAL_PAGES => ceil($resource->total() / $resource->perPage()),
            DefaultData::TOTAL_ITEMS => $resource->total(),
            DefaultData::PAGE_SIZE => $resource->perPage(),
            DefaultData::PAGE_NUMBER => $resource->currentPage(),
            DefaultData::PREV_PAGE => $resource->currentPage() - 1,
            DefaultData::NEXT_PAGE => $resource->currentPage() + 1,
            DefaultData::LAST_PAGE => $resource->lastPage(),
            DefaultData::PAGINATION_TYPE => 'page'
        ];
    }
}
