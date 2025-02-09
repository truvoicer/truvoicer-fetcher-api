<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\CreateChildSrRequest;
use App\Http\Requests\Service\Request\CreateSrRequest;
use App\Http\Requests\Service\Request\DeleteBatchSrRequest;
use App\Http\Requests\Service\Request\DuplicateSrRequest;
use App\Http\Requests\Service\Request\OverrideChildSrRequest;
use App\Http\Requests\Service\Request\ResponseKey\PopulateSrResponseKeysRequest;
use App\Http\Requests\Service\Request\UpdateSrDefaultsRequest;
use App\Http\Requests\Service\Request\UpdateSrRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestCollection;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResource;
use App\Http\Resources\Service\ServiceRequest\SrTreeViewCollection;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\PopulateFactory;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\ResponseKeyPopulateService;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
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

    public function __construct(
        private ProviderService      $providerService,
        private ApiService           $apiServicesService,
        private SrService            $srService,
    )
    {
        parent::__construct();
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
    public function getProviderServiceRequestList(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
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

        $this->srService->getServiceRequestRepository()
            ->setSortField($request->get('sort', "name"))
            ->setOrderDir($request->get('order', "asc"))
            ->setLimit($request->get('count', -1));
        if (!$request->query->getBoolean('include_children', false)) {
            $this->srService->getServiceRequestRepository()->setWhereDoesntHave(['parentSrs']);
        }
        if ($request->query->getBoolean('show_nested_children', false)) {
            $this->srService->getServiceRequestRepository()->setWith(['childSrs']);
        }
        $getServices = $this->srService->getUserServiceRequestByProvider(
            $provider,
        );

        if ($request->query->getBoolean('tree_view', false)) {
            return $this->sendSuccessResponse(
                "success",
                new SrTreeViewCollection($getServices)
            );
        }
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
    public function getServiceRequestList(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->srService->getServiceRequestRepository()
            ->setSortField($request->get('sort', "name"))
            ->setOrderDir($request->get('order', "asc"))
            ->setLimit($request->get('count', -1))
            ->setWith(['provider']);
        if (!$request->query->getBoolean('include_children', false)) {
            $this->srService->getServiceRequestRepository()->setWhereDoesntHave(['parentSrs']);
        }
        if ($request->query->getBoolean('show_nested_children', false)) {
            $this->srService->getServiceRequestRepository()->setWith(['childSrs']);
        }
        $providerIds = $request->get('provider_ids', []);
        if (!count($providerIds)) {
            return $this->sendErrorResponse("Provider ids not found in the request.");
        }

        $getServices = $this->srService->getUserServiceRequestByProviderIds(
            $request->user(),
            $providerIds
        );

        if ($request->query->getBoolean('tree_view', false)) {
            return $this->sendSuccessResponse(
                "success",
                new SrTreeViewCollection($getServices)
            );
        }
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
        $create = $this->srService->createServiceRequest($provider, $request->validated());
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
        $create = $this->srService->createChildSr($provider, $serviceRequest, $request->validated());
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
        Provider        $provider,
        Sr              $serviceRequest,
        UpdateSrRequest $request
    ): \Illuminate\Http\JsonResponse
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

        $update = $this->srService->updateServiceRequest($serviceRequest, $request->validated());

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
    public function updateSrDefaults(
        Provider                $provider,
        Sr                      $serviceRequest,
        UpdateSrDefaultsRequest $request
    ): \Illuminate\Http\JsonResponse
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

        $update = $this->srService->updateSrDefaults($serviceRequest, $request->validated());

        if (!$update) {
            return $this->sendErrorResponse("Error updating sr default data");
        }
        return $this->sendSuccessResponse(
            "Sr default data updated",
            new  ServiceRequestResource(
                $this->srService->getServiceRequestRepository()->getModel()
            )
        );
    }

    public function runSrRequest(
        Provider        $provider,
        Sr              $serviceRequest,
        Request $request,
        SrOperationsService $srOperationsService
    ): \Illuminate\Http\JsonResponse
    {
        $srOperationsService->getRequestOperation()->setProvider($provider);
        $srOperationsService->runOperationForSr(
            $serviceRequest,
            SrResponseKeySrRepository::ACTION_STORE
        );
        return $this->sendSuccessResponse(
            "Request ran successfully",
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
    ): JsonResponse|\Illuminate\Http\JsonResponse
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
        if ($requestType === 'response_keys') {
            $runApiRequest = $requestOperation->runOperation($data)->toArray();
        } else {
            $runApiRequest = $requestOperation->getOperationRequestContent($requestType, $data);
        }

        return new JsonResponse(
            $runApiRequest,
            Response::HTTP_OK
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
    public function populateSrResponseKeys(
        Provider          $provider,
        Sr                $sr,
        PopulateFactory $populateFactory,
        PopulateSrResponseKeysRequest           $request
    ): JsonResponse|\Illuminate\Http\JsonResponse
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
        $populateFactory->setOverwrite($request->validated('overwrite', false));
        $populateFactory->setUser($request->user());
        $populateFactory->setData($request->validated());
        $populateFactory->create(
            $sr,
            $request->validated('srs'),
            $request->validated('query', [])
        );

        if ($populateFactory->hasErrors()) {
            return $this->sendErrorResponse(
                "Error running request",
                $populateFactory->getErrors(),
            );
        }
        return $this->sendSuccessResponse(
            "Request ran successfully",
        );
    }

    /**
     * Duplicate a providers' service request
     *
     */
    public function duplicateServiceRequest(
        Provider $provider,
        Sr       $serviceRequest,
        DuplicateSrRequest  $request
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
            $request->validated('label'),
            $request->validated('include_child_srs', true),
            null,
            $request->validated('parent_sr')
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
        DuplicateSrRequest  $request
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
            $request->validated('label'),
            $request->validated('include_child_srs', true),
            null,
            $request->validated('parent_sr')
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

    public function deleteBatchServiceRequest(
        Provider             $provider,
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
                $serviceRequest->with([
                    'category',
                    's',
                    'srChildSr' => function ($query) {
                        $query->with(['parentSr' => function ($query) {
                            $query->without(['childSrs']);
                        }]);
                    }
                ])->where('id', $serviceRequest->id)->first()
            )
        );
    }

}
