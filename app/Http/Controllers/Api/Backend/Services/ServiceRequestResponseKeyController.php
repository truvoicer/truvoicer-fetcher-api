<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResponseKeyResource;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;
use App\Models\ServiceResponseKey;
use App\Services\ApiServices\ServiceRequests\RequestResponseKeysService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceRequestResponseKeyController extends Controller
{
    private RequestResponseKeysService $requestResponseKeysService;

    /**
     * ServiceRequestResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param RequestResponseKeysService $requestResponseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        RequestResponseKeysService $requestResponseKeysService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->requestResponseKeysService = $requestResponseKeysService;
    }

    /**
     * Get a list of service request response keys.
     * Returns a list of service request response keys based on the request query parameters
     *
     */
    public function getRequestResponseKeyList(Provider $provider, ServiceRequest $serviceRequest, Request $request)
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
            $responseKeys = $this->requestResponseKeysService->getRequestResponseKeys(
                $serviceRequest,
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );

        return $this->sendSuccessResponse(
            "success",
            ServiceRequestResponseKeyResource::collection($responseKeys)
        );
    }

    /**
     * Get a single service request response key
     * Returns a single service request response key based on the id passed in the request url
     *
     */
    public function getRequestResponseKey(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceResponseKey $serviceResponseKey,
        Request $request
    ) {
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
        $getRequestResponseKey = $this->requestResponseKeysService->getRequestResponseKeyObjectById(
            $serviceRequest,
            $serviceResponseKey
        );
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResponseKeyResource($getRequestResponseKey)
        );
    }

    /**
     * Create an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createRequestResponseKey(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceResponseKey $serviceResponseKey,
        Request $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->requestResponseKeysService->createRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $request->all()
        );
        if (!$create) {
            return $this->sendErrorResponse("Error adding response key.");
        }
        return $this->sendSuccessResponse(
            "Successfully added response key.",
            new ServiceRequestResponseKeyResource(
                $this->requestResponseKeysService->getRequestResponseKeyRepository()->getModel()
            )
        );
    }

    /**
     * Update an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateRequestResponseKey(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceRequestResponseKey $serviceResponseKey,
        Request $request
    ) {
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
        $update = $this->requestResponseKeysService->updateRequestResponseKey(
            $serviceResponseKey,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key updated",
            new ServiceRequestResponseKeyResource(
                $this->requestResponseKeysService->getRequestResponseKeyRepository()->getModel()
            )
        );
    }

    /**
     * Delete  an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteRequestResponseKey(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceRequestResponseKey $serviceResponseKey,
        Request $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        if (!$this->requestResponseKeysService->deleteRequestResponseKey($serviceResponseKey)) {
            return $this->sendErrorResponse(
                "Error deleting service response key"
            );
        }
        return $this->sendSuccessResponse(
            "Response key service deleted."
        );
    }
}
