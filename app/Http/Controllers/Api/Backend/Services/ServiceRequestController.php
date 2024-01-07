<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResource;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ApiService;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains Api endpoint functions for api service related request operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceRequestController extends Controller
{

    // Initialise services variables for this controller
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private RequestService $requestService;

    /**
     * ServiceRequestController constructor.
     * Initialise services for this controller
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param RequestService $requestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        RequestService $requestService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestService = $requestService;
    }

    /**
     * Get list of service requests function
     * Returns a list of service requests based on the request query parameters
     *
     */
    public function getServiceRequestList(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }

        $getServices = $this->requestService->getUserServiceRequestByProvider(
            $provider,
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );

        return $this->sendSuccessResponse(
            "success",
            ServiceRequestResource::collection($getServices)
        );
    }


    /**
     * Get a provider service request based on the provider and service in the request data
     * Returns a single provider service request
     *
     */
    public function getProviderServiceRequest(
        Provider $provider,
        S        $service,
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
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResource(
                $this->requestService->getProviderServiceRequest($service, $provider)
            )
        );
    }

    /**
     * Create an api service request based on request POST data
     * Returns json success message and api service request data on successful creation
     * Returns error response and message on fail
     *
     * @param Provider $provider
     * @param Request $request
     * @return JsonResponse
     */
    public function createServiceRequest(Provider $provider, Request $request): JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->requestService->createServiceRequest($provider, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error inserting service request");
        }
        return $this->sendSuccessResponse(
            "Service request inserted",
            new  ServiceRequestResource(
                $this->requestService->getServiceRequestRepository()->getModel()
            )
        );
    }

    /**
     * Update an api service request based on request POST data
     * Returns json success message and api service request data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Request  $request
    ): \Illuminate\Http\JsonResponse {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $update = $this->requestService->updateServiceRequest($serviceRequest, $request->all());

        if (!$update) {
            return $this->sendErrorResponse("Error updating service request");
        }
        return $this->sendSuccessResponse(
            "Service request updated",
            new  ServiceRequestResource(
                $this->requestService->getServiceRequestRepository()->getModel()
            )
        );
    }

    /**
     * Runs an api request to a provider based on the request query data
     *
     * Required fields in query data:
     * - request_type
     * - provider
     * - (Parameters set for the provider service request)
     *
     */
    public function runApiRequest(
        Provider $provider,
        RequestOperation $requestOperation,
        Request $request
    ): JsonResponse|\Illuminate\Http\JsonResponse {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $data = $request->query->all();

        if (empty($data["request_type"])) {
            return $this->sendErrorResponse("Api request type not found in the request.");
        }

        $requestOperation->setProviderName($data['provider']);
        $requestOperation->setApiRequestName($data["request_type"]);
        $runApiRequest = $requestOperation->getOperationRequestContent($data);

        return new JsonResponse(
            $this->serializerService->entityToArray($runApiRequest),
            Response::HTTP_OK
        );
    }

    /**
     * Duplicate a providers' service request
     *
     */
    public function duplicateServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Request  $request
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
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $update = $this->requestService->duplicateServiceRequest(
            $serviceRequest,
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$update) {
            return $this->sendErrorResponse("Error duplicating service request");
        }
        return $this->sendSuccessResponse(
            "Service request duplicated",
            new ServiceRequestResource($update)
        );
    }

    /**
     * Merge a providers' service request response keys
     *
     */
    public function mergeServiceRequestResponseKeys(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $update = $this->requestService->mergeRequestResponseKeys(
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error merging response keys");
        }
        return $this->sendSuccessResponse("Request keys merge successful");
    }

    /**
     * Delete an api service request based on request POST data
     * Returns json success message and api service request data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Request  $request
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
        if (!$this->requestService->deleteServiceRequest($serviceRequest)) {
            return $this->sendErrorResponse(
                "Error deleting service request",
            );
        }
        return $this->sendSuccessResponse(
            "Service request deleted.",
        );
    }

    /**
     * Get a single api service request
     * Returns a single api service request based on the id passed in the request url
     *
     */
    public function getServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Request  $request
    ): \Illuminate\Http\JsonResponse {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResource($serviceRequest)
        );
    }
}
