<?php
namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Truvoicer\TruFetcherGet\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for search related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
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
        if (count($searchArray) > 0) {
            $searchArray["items"] = array_map(function ($item) {
                return $this->serializerService->entityToArray($item, ["search"]);
            }, $searchArray["items"]);
        }
        return $this->sendSuccessResponse("success", $searchArray);
    }
}
