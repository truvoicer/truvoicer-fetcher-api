<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\RequestResponseKeysService;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ResponseKeysService;
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
    const DEFAULT_ENTITY = "provider";

    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private RequestResponseKeysService $requestResponseKeysService;

    /**
     * ServiceRequestResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param RequestResponseKeysService $requestResponseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        RequestResponseKeysService $requestResponseKeysService,
        AccessControlService $accessControlService,
        Request $request
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestResponseKeysService = $requestResponseKeysService;
    }

    /**
     * Get a list of service request response keys.
     * Returns a list of service request response keys based on the request query parameters
     *
     */
    public function getRequestResponseKeyList(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        $requestResponseKeysArray = [];
        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            self::DEFAULT_ENTITY,
            $provider,
            $request->user(),
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ],
            false
        );
        if ($request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN)) ||
            $isPermitted
        ) {
            $responseKeys = $this->requestResponseKeysService->getRequestResponseKeys(
                $serviceRequest,
                $request->get('sort', "key_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );
            $requestResponseKeysArray = $this->serializerService->entityArrayToArray($responseKeys, ["response_key"]);
        }
        return $this->sendSuccessResponse("success", $requestResponseKeysArray);
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
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            );
        }
        $getRequestResponseKey = $this->requestResponseKeysService->getRequestResponseKeyObjectById(
            $serviceRequest,
            $serviceResponseKey
        );
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($getRequestResponseKey, ["response_key"])
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
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $requestData = $this->httpRequestService->getRequestData($request);
        $create = $this->requestResponseKeysService->createRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $requestData->data
        );
        if (!$create) {
            return $this->sendErrorResponse("Error adding response key.");
        }
        return $this->sendSuccessResponse(
            "Successfully added response key.",
            $this->serializerService->entityToArray($create, ["single"])
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
        ServiceResponseKey $serviceResponseKey,
        Request $request
    ) {
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $update = $this->requestResponseKeysService->updateRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key updated",
            $this->serializerService->entityToArray($update, ['single'])
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
        ServiceResponseKey $serviceResponseKey,
        Request $request
    ) {
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            );
        }
        $delete = $this->requestResponseKeysService->deleteRequestResponseKey($serviceRequest, $serviceResponseKey);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting service response key",
                $this->serializerService->entityToArray($delete, ['single'])
            );
        }
        return $this->sendSuccessResponse(
            "Response key service deleted.",
            $this->serializerService->entityToArray($delete, ['single'])
        );
    }
}
