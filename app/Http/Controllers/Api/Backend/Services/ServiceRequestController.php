<?php
namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Entity\Provider;
use App\Entity\Service;
use App\Entity\ServiceRequest;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains Api endpoint functions for api service related request operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 *
 * @Route("/api/provider/{provider}/service/request")
 */
class ServiceRequestController extends Controller
{
    const DEFAULT_ENTITY = "provider";

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
    public function __construct(ProviderService $providerService, HttpRequestService $httpRequestService,
                                SerializerService $serializerService, ApiService $apiServicesService,
                                RequestService $requestService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestService = $requestService;
    }

    /**
     * Get list of service requests function
     * Returns a list of service requests based on the request query parameters
     *
     * @Route("/list", name="api_get_service_request_list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getServiceRequestList(Provider $provider, Request $request)
    {
        $getServices = $this->requestService->getUserAdminServiceRequestByProvider(
            $provider,
            $request->get('sort', "service_request_name"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );

        if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN')) {
            return $this->sendSuccessResponse("success",
                $this->serializerService->entityArrayToArray($getServices, ["list"])
            );
        }
        $this->accessControlService->checkPermissionsForEntity(
            self::DEFAULT_ENTITY, $provider, $request->user(),
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ]
        );
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($getServices, ["list"])
        );
    }


    /**
     * Get a provider service request based on the provider and service in the request data
     * Returns a single provider service request
     *
     * @Route("/service/{service}", name="api_get_provider_service_request", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getProviderServiceRequest(Provider $provider, Service $service, Request $request)
    {
        $data = $request->query->all();
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray(
                $this->requestService->getProviderServiceRequest($service, $provider)
            )
        );
    }

    /**
     * Create an api service request based on request POST data
     * Returns json success message and api service request data on successful creation
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/create", name="api_create_service_request", methods={"POST"})
     * @return JsonResponse
     */
    public function createServiceRequest(Provider $provider, Request $request) {
        $data = $this->httpRequestService->getRequestData($request, true);
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ],
            );
        }
        $create = $this->requestService->createServiceRequest($provider, $data);
        if(!$create) {
            return $this->sendErrorResponse("Error inserting service request");
        }
        return $this->sendSuccessResponse("Service request inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * Update an api service request based on request POST data
     * Returns json success message and api service request data on successful update
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceRequest}/update", name="api_update_service_request", methods={"POST"})
     * @return JsonResponse
     */
    public function updateServiceRequest(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        $data = $this->httpRequestService->getRequestData($request, true);
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $update = $this->requestService->updateServiceRequest($provider, $serviceRequest, $data);

        if(!$update) {
            return $this->sendErrorResponse("Error updating service request");
        }
        return $this->sendSuccessResponse("Service request updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Runs an api request to a provider based on the request query data
     *
     * Required fields in query data:
     * - request_type
     * - provider
     * - (Parameters set for the provider service request)
     *
     * @param RequestOperation $requestOperation
     * @param Request $request
     * @return JsonResponse
     * @Route("/test-run", name="run_service_api_request", methods={"GET"})
     */
    public function runApiRequest(Provider $provider, RequestOperation $requestOperation, Request $request) {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        $data = $request->query->all();

        if (!isset($data["request_type"]) || $data["request_type"] === null || $data["request_type"] === "") {
            return $this->sendErrorResponse("Api request type not found in the request.");
        }

        $requestOperation->setProviderName($data['provider']);
        $requestOperation->setApiRequestName($data["request_type"]);
        $runApiRequest = $requestOperation->getOperationRequestContent($data);

        return new JsonResponse(
            $this->serializerService->entityToArray($runApiRequest),
            Response::HTTP_OK);
    }

    /**
     * Duplicate a providers' service request
     *
     * @param Request $request
     * @Route("/{serviceRequest}/duplicate", name="api_duplicate_service_request", methods={"POST"})
     * @return JsonResponse
     */
    public function duplicateServiceRequest(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $update = $this->requestService->duplicateServiceRequest(
            $serviceRequest,
            $this->httpRequestService->getRequestData($request, true));

        if(!$update) {
            return $this->sendErrorResponse("Error duplicating service request");
        }
        return $this->sendSuccessResponse("Service request duplicated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Merge a providers' service request response keys
     *
     * @param Request $request
     * @Route("/response-keys/merge", name="api_merge_service_request_response_keys", methods={"POST"})
     * @return JsonResponse
     */
    public function mergeServiceRequestResponseKeys(Provider $provider, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $update = $this->requestService->mergeRequestResponseKeys(
            $this->httpRequestService->getRequestData($request, true));

        if(!$update) {
            return $this->sendErrorResponse("Error merging response keys");
        }
        return $this->sendSuccessResponse("Request keys merge successful");
    }

    /**
     * Delete an api service request based on request POST data
     * Returns json success message and api service request data on successful delete
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceRequest}/delete", name="api_delete_service_request", methods={"POST"})
     * @return JsonResponse
     */
    public function deleteServiceRequest(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            );
        }
        $delete = $this->requestService->deleteServiceRequest($serviceRequest);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting service request", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->sendSuccessResponse("Service request deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }

    /**
     * Get a single api service request
     * Returns a single api service request based on the id passed in the request url
     *
     * @Route("/{serviceRequest}", name="api_get_service_request", methods={"GET"})
     * @param ServiceRequest $serviceRequest
     * @return JsonResponse
     */
    public function getServiceRequest(Provider $provider, ServiceRequest $serviceRequest)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($serviceRequest, ["single"])
        );
    }
}
