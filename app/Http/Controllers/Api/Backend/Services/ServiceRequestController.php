<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\CreateChildSrRequest;
use App\Http\Requests\Service\Request\CreateSrRequest;
use App\Http\Requests\Service\Request\DeleteBatchSrRequest;
use App\Http\Requests\Service\Request\OverrideChildSrRequest;
use App\Http\Requests\Service\Request\UpdateServiceRequest;
use App\Http\Requests\Service\Request\UpdateSrRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestCollection;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResource;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
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
    private SrService $srService;

    /**
     * ServiceRequestController constructor.
     * Initialise services for this controller
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param SrService $requestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService      $providerService,
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        ApiService           $apiServicesService,
        SrService            $requestService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->srService = $requestService;
    }

    public function getTypeList(): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse(
            "success",
            $this->srService->getServiceRequestRepository()::SR_TYPES
        );
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

        $getServices = $this->srService->getUserServiceRequestByProvider(
            $provider,
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );

        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestCollection($getServices)
        );
    }
    /**
     * Get list of service requests function
     * Returns a list of service requests based on the request query parameters
     *
     */
    public function getChildServiceRequestList(Provider $provider, Sr $serviceRequest, Request $request): \Illuminate\Http\JsonResponse
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

        $getServices = $this->srService->getUserChildSrsByProvider(
            $provider,
            $serviceRequest,
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );

        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestCollection($getServices)
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
                $this->srService->getProviderServiceRequest($service, $provider)
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
    public function createServiceRequest(Provider $provider, CreateSrRequest $request): JsonResponse
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
        $create = $this->srService->createServiceRequest($provider, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error inserting service request");
        }
        return $this->sendSuccessResponse(
            "Service request inserted",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
        );
    }

    /**
     * Create an api service request based on request POST data
     * Returns json success message and api service request data on successful creation
     * Returns error response and message on fail
     *
     * @param Provider $provider
     * @param Sr $serviceRequest
     * @param CreateSrRequest $request
     * @return JsonResponse
     */
    public function createChildServiceRequest(Provider $provider, Sr $serviceRequest, CreateChildSrRequest $request): JsonResponse
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
        $create = $this->srService->createChildSr($provider, $serviceRequest, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error inserting service request");
        }
        return $this->sendSuccessResponse(
            "Service request inserted",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
        );
    }
    public function overrideChildServiceRequest(Provider $provider, Sr $serviceRequest, Sr $childSr, OverrideChildSrRequest $request): JsonResponse
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
        $create = $this->srService->overrideChildSr($childSr, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error inserting service request");
        }
        return $this->sendSuccessResponse(
            "Service request inserted",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
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
        UpdateSrRequest  $request
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

        $update = $this->srService->updateServiceRequest($serviceRequest, $request->all());

        if (!$update) {
            return $this->sendErrorResponse("Error updating service request");
        }
        return $this->sendSuccessResponse(
            "Service request updated",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
        );
    }
    /**
     * Update an api service request based on request POST data
     * Returns json success message and api service request data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateChildServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Sr       $childSr,
        UpdateSrRequest  $request
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
        $update = $this->srService->updateServiceRequest($childSr, $request->all());

        if (!$update) {
            return $this->sendErrorResponse("Error updating child service request");
        }
        return $this->sendSuccessResponse(
            "Child service request updated",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
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
        Provider          $provider,
        ApiRequestService $requestOperation,
        Request           $request
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
        $requestOperation->setApiRequestName($data["sr_name"]);
        $requestType = 'raw';
        if (!empty($data['request_type'])) {
            $requestType = $data['request_type'];
        }
        $requestOperation->setUser($request->user());
        if ($requestType === 'json') {
            $runApiRequest = $requestOperation->runOperation($data)->toArray();
        } else {
            $runApiRequest = $requestOperation->getOperationRequestContent($data);
        }

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
        $update = $this->srService->duplicateServiceRequest(
            $serviceRequest,
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$update) {
            return $this->sendErrorResponse("Error duplicating service request");
        }
        return $this->sendSuccessResponse(
            "Service request duplicated",
            new ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
        );
    }
    public function duplicateChildServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Sr       $childSr,
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
        $update = $this->srService->duplicateServiceRequest(
            $childSr,
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$update) {
            return $this->sendErrorResponse("Error duplicating service request");
        }
        return $this->sendSuccessResponse(
            "Service request duplicated",
            new ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
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
        $update = $this->srService->mergeRequestResponseKeys(
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
        if (!$this->srService->deleteServiceRequest($serviceRequest)) {
            return $this->sendErrorResponse(
                "Error deleting service request",
            );
        }
        return $this->sendSuccessResponse(
            "Service request deleted.",
        );
    }
    public function deleteChildServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Sr       $childSr,
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
        if (!$this->srService->deleteServiceRequest($childSr)) {
            return $this->sendErrorResponse(
                "Error deleting child service request",
            );
        }
        return $this->sendSuccessResponse(
            "Child service request deleted.",
        );
    }
    public function deleteBatchServiceRequest(
        Provider $provider,
        DeleteBatchSrRequest $request
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

        if (!$this->srService->deleteBatchServiceRequests($request->get('ids'))) {
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
            new ServiceRequestResource(
                $serviceRequest->with(['category', 's'])->where('id', $serviceRequest->id)->first()
            )
        );
    }
    public function getChildServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        Sr       $childSr,
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
            new ServiceRequestResource(
                $childSr
            )
        );
    }
}
