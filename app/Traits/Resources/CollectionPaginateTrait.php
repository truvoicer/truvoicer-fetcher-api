<?php

namespace App\Traits\Resources;

use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use Illuminate\Pagination\LengthAwarePaginator;

trait CollectionPaginateTrait
{
    public function buildLinks(LengthAwarePaginator $resource)
    {
        return [
            DataConstants::TOTAL_PAGES => ceil($resource->total() / $resource->perPage()),
            DataConstants::TOTAL_ITEMS => $resource->total(),
            DataConstants::PAGE_SIZE => $resource->perPage(),
            DataConstants::PAGE_NUMBER => $resource->currentPage(),
            DataConstants::PREV_PAGE => $resource->currentPage() - 1,
            DataConstants::NEXT_PAGE => $resource->currentPage() + 1,
            DataConstants::LAST_PAGE => $resource->lastPage(),
            DataConstants::PAGINATION_TYPE => 'page'
        ];
    }
}
