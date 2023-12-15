<?php
namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\Permission\AccessControlService;
use App\Services\SearchService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for search related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 */
class SearchController extends Controller
{
    private SearchService $searchService;

    /**
     * SearchController constructor.
     * Initialise services to be used in this class
     *
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param SearchService $searchService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        SerializerService $serializerService, HttpRequestService $httpRequestService, SearchService $searchService,
        AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->searchService = $searchService;
    }

    /**
     * Performs a database search based on query parameters in the get request
     * Returns array of search results
     *
     * @Route("/api/admin/search/{query}", name="api_admin_search", methods={"GET"})
     * @param string $query
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
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
