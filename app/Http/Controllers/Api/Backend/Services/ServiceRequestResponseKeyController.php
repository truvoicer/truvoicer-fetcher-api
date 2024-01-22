<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\ResponseKey\CreateServiceRequestResponseKeyRequest;
use App\Http\Requests\Service\Request\ResponseKey\DeleteBatchSrResponseKeyRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResponseKeyResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
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
    private SrResponseKeyService $srResponseKeyService;

    /**
     * ServiceRequestResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param SrResponseKeyService $requestResponseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        SrResponseKeyService $requestResponseKeysService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->srResponseKeyService = $requestResponseKeysService;
    }

    /**
     * Get a list of service request response keys.
     * Returns a list of service request response keys based on the request query parameters
     *
     */
    public function getRequestResponseKeyList(Provider $provider, Sr $serviceRequest, Request $request)
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
        if (!$this->srResponseKeyService->validateSrResponseKeys($serviceRequest, true)) {
            return $this->sendErrorResponse("Error validating response keys");
        }
            $responseKeys = $this->srResponseKeyService->getRequestResponseKeys(
                $serviceRequest,
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                $request->get('count', -1)
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
        Provider     $provider,
        Sr           $serviceRequest,
        SrResponseKey $srResponseKey,
        Request      $request
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
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResponseKeyResource($srResponseKey)
        );
    }
    public function getRequestResponseKeyByResponseKey(
        Provider     $provider,
        Sr           $serviceRequest,
        SResponseKey $sResponseKey,
        Request      $request
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
        $find = $this->srResponseKeyService->getRequestResponseKeyByResponseKey(
            $serviceRequest,
            $sResponseKey
        );
        if (!$find) {
            return $this->sendErrorResponse("Error finding response key.");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResponseKeyResource($find)
        );
    }

    /**
     * Create an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createRequestResponseKey(
        Provider     $provider,
        Sr           $serviceRequest,
        CreateServiceRequestResponseKeyRequest $request
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
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $serviceRequest->s()->first(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->srResponseKeyService->createSrResponseKey(
            $request->user(),
            $serviceRequest,
            $request->get('name'),
            $request->all([
                'value',
                'show_in_response',
                'has_array_value',
                'array_keys',
                'list_item',
                'return_data_type',
                'append_extra_data',
                'append_extra_data_value',
                'prepend_extra_data',
                'prepend_extra_data_value',
                'is_service_request'
            ])
        );
        if (!$create) {
            return $this->sendErrorResponse("Error adding response key.");
        }
        return $this->sendSuccessResponse(
            "Successfully added response key.",
            new ServiceRequestResponseKeyResource(
                $this->srResponseKeyService->getSrResponseKeyRepository()->getModel()
            )
        );
    }

    public function saveRequestResponseKey(
        Provider     $provider,
        Sr           $serviceRequest,
        SResponseKey $sResponseKey,
        Request      $request
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
        $create = $this->srResponseKeyService->saveSrResponseKey(
            $request->user(),
            $serviceRequest,
            $sResponseKey,
            $request->all()
        );
        if (!$create) {
            return $this->sendErrorResponse("Error adding response key.");
        }
        return $this->sendSuccessResponse(
            "Successfully added response key.",
            new ServiceRequestResponseKeyResource(
                $this->srResponseKeyService->getSrResponseKeyRepository()->getModel()
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
        Provider      $provider,
        Sr            $serviceRequest,
        SrResponseKey $srResponseKey,
        Request       $request
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
        $update = $this->srResponseKeyService->updateRequestResponseKey(
            $request->user(),
            $srResponseKey,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key updated",
            new ServiceRequestResponseKeyResource(
                $this->srResponseKeyService->getSrResponseKeyRepository()->getModel()
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
        Provider      $provider,
        Sr            $serviceRequest,
        SrResponseKey $srResponseKey,
        Request       $request
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
        if (!$this->srResponseKeyService->deleteRequestResponseKey($srResponseKey)) {
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
        DeleteBatchSrResponseKeyRequest $request
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

        if (!$this->srResponseKeyService->deleteBatch($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting service request response keys",
            );
        }
        return $this->sendSuccessResponse(
            "Service request response keys deleted.",
        );
    }
}
