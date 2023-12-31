<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\CreateServiceRequestConfigRequest;
use App\Http\Requests\Service\UpdateServiceRequestConfigRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestConfigResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
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

    // Initialise services for this controller
    private RequestConfigService $requestConfigService;

    /**
     * ServiceRequestConfigController constructor.
     * Initialise services for this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param RequestConfigService $requestConfigService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        RequestConfigService $requestConfigService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        // Initialise services for this controller
        $this->requestConfigService = $requestConfigService;
    }

    /**
     * Get list of service request configs function
     * Returns a list of service request configs based on the request query parameters
     *
     */
    public function getRequestConfigList(Provider $provider, Sr $serviceRequest, Request $request): \Illuminate\Http\JsonResponse
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
            $serviceRequest,
            $request->get('sort', "name"),
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
        Sr       $serviceRequest,
        SrConfig $serviceRequestConfig,
        Request  $request
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
        Provider                          $provider,
        Sr                                $serviceRequest,
        CreateServiceRequestConfigRequest $request
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
        Provider                          $provider,
        Sr                                $serviceRequest,
        SrConfig                          $serviceRequestConfig,
        UpdateServiceRequestConfigRequest $request
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
        Sr       $serviceRequest,
        SrConfig $serviceRequestConfig,
        Request  $request
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
