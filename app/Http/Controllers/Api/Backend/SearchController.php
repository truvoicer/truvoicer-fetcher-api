<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\SearchService;

/**
 * Contains api endpoint functions for search related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 */
class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
    ) {
        parent::__construct();
    }

    public function search(string $query)
    {
        $searchArray = $this->searchService->performSearch($query);

        return $this->sendSuccessResponse('success', $searchArray);
    }
}
