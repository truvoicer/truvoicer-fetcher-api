<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use Truvoicer\TfDbReadCore\Http\Resources\ProviderMinimalCollection;
use Truvoicer\TfDbReadCore\Models\Category;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;
use Truvoicer\TfDbReadCore\Services\ApiServices\SResponseKeysService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TfDbReadCore\Services\Category\CategoryService;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
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

    public function __construct(
        private ProviderService $providerService,
    ) {
        parent::__construct();
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
