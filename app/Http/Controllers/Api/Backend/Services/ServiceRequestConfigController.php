<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestConfigResource;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestConfig;
use App\Services\ApiServices\ApiService;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestConfigService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for service request config related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceRequestConfigController extends Controller
{
    const DEFAULT_ENTITY = "provider";

    // Initialise services for this controller
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private RequestConfigService $requestConfigService;

    /**
     * ServiceRequestConfigController constructor.
     * Initialise services for this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param RequestConfigService $requestConfigService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        RequestConfigService $requestConfigService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        // Initialise services for this controller
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestConfigService = $requestConfigService;
    }

    /**
     * Get list of service request configs function
     * Returns a list of service request configs based on the request query parameters
     *
     */
    public function getRequestConfigList(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $findRequestConfigs = $this->requestConfigService->findByParams(
            $request->get('service_request_id'),
            $request->get('sort', "item_name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse("success",
            ServiceRequestConfigResource::collection(
                $findRequestConfigs
            )
        );
    }

    /**
     * Get a single service request config
     * Returns a single service request config based on the id passed in the request url
     *
     */
    public function getServiceRequestConfig(
        Provider $provider,
        ServiceRequestConfig $serviceRequestConfig,
        Request $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestConfigResource($serviceRequestConfig)
        );
    }

    /**
     * Create an service request config based on request POST data
     * Returns json success message and service request config data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createRequestConfig(
        Provider $provider,
        ServiceRequest $serviceRequest,
        Request $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->requestConfigService->createRequestConfig(
            $serviceRequest,
            $request->all()
        );

        if (!$create) {
            return $this->sendErrorResponse("Error inserting config item");
        }
        return $this->sendSuccessResponse(
            "Config item inserted",
            new ServiceRequestConfigResource(
                $this->requestConfigService->getRequestConfigRepo()->getModel()
            )
        );
    }

    /**
     * Update a service request config based on request POST data
     * Returns json success message and service request config data on successful update
     *
     * Returns error response and message on fail
     */
    public function updateRequestConfig(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceRequestConfig $serviceRequestConfig,
        Request $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $update = $this->requestConfigService->updateRequestConfig(
            $serviceRequest,
            $serviceRequestConfig,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating config item");
        }
        return $this->sendSuccessResponse(
            "Config item updated",
            new ServiceRequestConfigResource(
                $this->requestConfigService->getRequestConfigRepo()->getModel()
            )
        );
    }

    /**
     * Delete a service request config based on request POST data
     * Returns json success message and service request config data on successful deletion
     *
     * Returns error response and message on fail
     */
    public function deleteRequestConfig(
        Provider $provider,
        ServiceRequestConfig $serviceRequestConfig,
        Request $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        if (!$this->requestConfigService->deleteRequestConfig($serviceRequestConfig)) {
            return $this->sendErrorResponse(
                "Error deleting config item"
            );
        }
        return $this->sendSuccessResponse(
            "Config item deleted."
        );
    }
}
