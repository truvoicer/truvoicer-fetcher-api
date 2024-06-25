<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\ResponseKey\CreateSResponseKeyRequest;
use App\Http\Requests\Service\ResponseKey\DeleteBatchSResponseKeyRequest;
use App\Http\Requests\Service\ResponseKey\UpdateServiceResponseKeyRequest;
use App\Http\Resources\Service\ServiceResponseKeyCollection;
use App\Http\Resources\Service\ServiceResponseKeyResource;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\SResponseKeysService;
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
    private SResponseKeysService $responseKeysService;

    /**
     * ServiceResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param SResponseKeysService $responseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService      $providerService,
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        ApiService           $apiServicesService,
        SResponseKeysService $responseKeysService,
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
    public function getServiceResponseKeyList(S $service, Request $request)
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
            new ServiceResponseKeyCollection(
                $this->responseKeysService->getResponseKeysByService(
                    $service,
                    $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
                )
            )
        );
    }

    /**
     * Get a single service response key
     * Returns a single service response key based on the id passed in the request url
     *
     */
    public function getServiceResponseKey(S $service, SResponseKey $serviceResponseKey, Request $request)
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
    public function loadDefaultServiceResponseKeys(S $service, Request $request)
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
        if (!$this->responseKeysService->createDefaultServiceResponseKeys($service)) {
            return $this->sendErrorResponse("Error loading default service response keys");
        }
        return $this->sendSuccessResponse(
            "Default service response keys loaded",
        );
    }

    /**
     * Create an api service response key based on request POST data
     * Returns json success message and api service response key data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createServiceResponseKey(S $service, CreateSResponseKeyRequest $request)
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
    public function updateServiceResponseKey(S $service, SResponseKey $serviceResponseKey, UpdateServiceResponseKeyRequest $request)
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
    public function deleteServiceResponseKey(S $service, SResponseKey $serviceResponseKey, Request $request)
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
    public function deleteBatch(
        Provider      $provider,
        Sr            $serviceRequest,
        DeleteBatchSResponseKeyRequest $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }

        if (!$this->responseKeysService->deleteBatch($request->validated('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting service response keys",
            );
        }
        return $this->sendSuccessResponse(
            "Service response keys deleted.",
        );
    }
}
