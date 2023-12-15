<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Entity\Category;
use App\Entity\Service;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\ApiServices\ServiceRequests\RequestConfigService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
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

    /**
     * @Route("/api/category/{category_name}/providers", name="api_get_category_provider_list", methods={"GET"})
     * @param Category $category
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
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
     * @Route("/api/service/response/key/list", name="api_frontend_service_response_key_list", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * @Route("/api/service/list", name="api_frontend_service_list", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function frontendServiceList(Request $request, ApiService $apiService)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($apiService->findByParams(), ["list"]));
    }
}
