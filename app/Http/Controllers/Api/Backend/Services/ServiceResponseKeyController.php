<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\CreateServiceResponseKeyRequest;
use App\Http\Requests\Service\UpdateServiceResponseKeyRequest;
use App\Http\Resources\Service\ServiceResponseKeyResource;
use App\Models\Service;
use App\Models\ServiceResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceResponseKeyController extends Controller
{
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private ResponseKeysService $responseKeysService;

    /**
     * ServiceResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param ResponseKeysService $responseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        ResponseKeysService $responseKeysService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->responseKeysService = $responseKeysService;
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function getServiceResponseKeyList(Service $service, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        return $this->sendSuccessResponse(
            "success",
            ServiceResponseKeyResource::collection(
                $this->responseKeysService->getResponseKeysByService($service)
            )
        );
    }

    /**
     * Get a single service response key
     * Returns a single service response key based on the id passed in the request url
     *
     */
    public function getServiceResponseKey(Service $service, ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceResponseKeyResource($serviceResponseKey)
        );
    }

    /**
     * Create an api service response key based on request POST data
     * Returns json success message and api service response key data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createServiceResponseKey(Service $service, CreateServiceResponseKeyRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        $create = $this->responseKeysService->createServiceResponseKeys(
            $service,
            $request->validated(),
        );

        if (!$create) {
            return $this->sendErrorResponse("Error inserting service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key inserted",
            new ServiceResponseKeyResource(
                $this->responseKeysService->getResponseKeyRepository()->getModel()
            )
        );
    }

    /**
     * Update an api service response key based on request POST data
     * Returns json success message and api service response key data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateServiceResponseKey(Service $service, ServiceResponseKey $serviceResponseKey, UpdateServiceResponseKeyRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        $update = $this->responseKeysService->updateServiceResponseKeys(
            $serviceResponseKey,
            $request->validated(),
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key updated",
            new ServiceResponseKeyResource(
                $this->responseKeysService->getResponseKeyRepository()->getModel()
            )
        );
    }

    /**
     * Delete an api service response key based on request POST data
     * Returns json success message and api service response key data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteServiceResponseKey(Service $service, ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        if (!$this->responseKeysService->deleteServiceResponseKey($serviceResponseKey)) {
            return $this->sendErrorResponse(
                "Error deleting service response key"
            );
        }
        return $this->sendSuccessResponse(
            "Response key service deleted."
        );
    }
}
