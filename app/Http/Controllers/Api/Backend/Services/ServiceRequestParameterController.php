<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;
use App\Services\ApiServices\ApiService;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestParametersService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request parameter related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceRequestParameterController extends Controller
{
    const DEFAULT_ENTITY = "provider";

    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private RequestParametersService $requestParametersService;

    /**
     * ServiceRequestParameterController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param RequestParametersService $requestParametersService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        RequestParametersService $requestParametersService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestParametersService = $requestParametersService;
    }

    /**
     * Get a list of service request parameters.
     * Returns a list of service request parameters based on the request query parameters
     *
     */
    public function getServiceRequestParameterList(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        $this->setAccessControlUser($request->user());
        $requestParametersArray = [];
        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            $provider,
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
            $findRequestParameters = $this->requestParametersService->findByParams(
                $serviceRequest,
                $request->get('sort', "parameter_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );
            $requestParametersArray = $this->serializerService->entityArrayToArray($findRequestParameters, ["list"]);
        }
        return $this->sendSuccessResponse("success", $requestParametersArray);
    }

    /**
     * Get a single service request parameter
     * Returns a single service request parameter based on the id passed in the request url
     *
     */
    public function getSingleServiceRequestParameters(
        Provider $provider,
        ServiceRequest $serviceRequest
    ) {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($serviceRequest, ["parameters"])
        );
    }

    /**
     * Get a single service request parameter
     * Returns a single service request parameter based on the id passed in the request url
     *
     */
    public function getServiceRequestParameter(
        Provider $provider,
        ServiceRequestParameter $serviceRequestParameter
    ) {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($serviceRequestParameter, ["single"])
        );
    }

    /**
     * Create an api service request parameter based on request POST data
     * Returns json success message and api service request data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createServiceRequestParameter(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $data = $this->httpRequestService->getRequestData($request, true);
        $create = $this->requestParametersService->createRequestParameter($serviceRequest, $data);
        if (!$create) {
            return $this->sendErrorResponse("Error inserting parameter");
        }
        return $this->sendSuccessResponse(
            "Parameter inserted",
            $this->serializerService->entityToArray($create, ['single'])
        );
    }

    /**
     * Update an api service request parameter based on request POST data
     * Returns json success message and api service request parameter data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateServiceRequestParameter(
        Provider $provider,
        ServiceRequest $serviceRequest,
        ServiceRequestParameter $serviceRequestParameter
    ) {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $data = $this->httpRequestService->getRequestData($request, true);
        $update = $this->requestParametersService->updateRequestParameter(
            $serviceRequestParameter,
            $serviceRequest,
            $data
        );
        if (!$update) {
            return $this->sendErrorResponse("Error updating parameter");
        }
        return $this->sendSuccessResponse(
            "Parameter updated",
            $this->serializerService->entityToArray($update, ['single'])
        );
    }

    /**
     * Delete an api service request parameter based on request POST data
     * Returns json success message and api service request parameter data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteServiceRequestParameter(
        Provider $provider,
        ServiceRequestParameter $serviceRequestParameter
    ) {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            );
        }
        $delete = $this->requestParametersService->deleteRequestParameter($serviceRequestParameter);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting parameter",
                $this->serializerService->entityToArray($delete, ['single'])
            );
        }
        return $this->sendSuccessResponse(
            "Parameter deleted.",
            $this->serializerService->entityToArray($delete, ['single'])
        );
    }
}
