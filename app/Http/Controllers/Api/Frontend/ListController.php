<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\ApiServices\ServiceRequests\RequestConfigService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ListController extends Controller
{
    private ProviderService $providerService;
    private CategoryService $categoryService;

    public function __construct(ProviderService $providerService, HttpRequestService $httpRequestService,
                                SerializerService $serializerService, CategoryService $categoryService,
                                AccessControlService $accessControlService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->categoryService = $categoryService;
    }

    public function getCategoryProviderList(Category $category, Request $request)
    {
        if ($category === null) {
            throw new BadRequestHttpException("Category doesn't exist");
        }
        if (!$request->query->has("filter") ||
            $request->query->get("filter") === null ||
            $request->query->get("filter") === ""
        ) {
            return $this->sendSuccessResponse("success",
                $this->categoryService->getCategoryProviderList($category, $request->user()));
        }
        return $this->sendSuccessResponse("success",
            $this->categoryService->getCategorySelectedProvidersList($request->query->get("filter"), $request->user()));
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function frontendServiceResponseKeyList(Request $request, ResponseKeysService $responseKeysService)
    {
        $data = $request->query->all();
        if (isset($data["service_id"])) {
            $responseKeys = $responseKeysService->getResponseKeysByServiceId($data['service_id']);
        } elseif (isset($data["service_name"])) {
            $responseKeys = $responseKeysService->getResponseKeysByServiceName($data['service_name']);
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($responseKeys, ["list"]));
    }
    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function frontendServiceList(Request $request, ApiService $apiService)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($apiService->findByParams(), ["list"]));
    }
}
