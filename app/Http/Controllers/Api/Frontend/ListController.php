<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderMinimalCollection;
use App\Models\Category;
use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
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

    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
    }

    public function getCategoryProviderList(S $service, Request $request)
    {
        if (!$request->query->has("filter") ||
            $request->query->get("filter") === null ||
            $request->query->get("filter") === ""
        ) {
            return $this->sendSuccessResponse(
                "success",
                new ProviderMinimalCollection($this->providerService->findProvidersByService($service, $request->user()))
            );
        }

        $selectedProvidersArray = explode(",", $request->query->get("filter"));
        $this->providerService->getProviderRepository()->addWhere('id', $selectedProvidersArray, 'IN');
        return $this->sendSuccessResponse(
            "success",
            new ProviderMinimalCollection(
                $this->providerService->findProviders($request->user())
            )
        );
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function frontendServiceResponseKeyList(Request $request, SResponseKeysService $responseKeysService)
    {
        $data = $request->query->all();
        if (isset($data["service_id"])) {
            $responseKeys = $responseKeysService->getResponseKeysByServiceId($data['service_id']);
        } elseif (isset($data["name"])) {
            $responseKeys = $responseKeysService->getResponseKeysByServiceName($data['name']);
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray($responseKeys, ["list"])
        );
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function frontendServiceList(Request $request, ApiService $apiService)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray($apiService->findByParams(), ["list"])
        );
    }
}
