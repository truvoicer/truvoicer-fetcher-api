<?php
namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Entity\Provider;
use App\Entity\ServiceRequest;
use App\Entity\ServiceRequestConfig;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestConfigService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains Api endpoint functions for service request config related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/provider/{provider}/service/request/{id}/config")
 */
class ServiceRequestConfigController extends Controller
{
    const DEFAULT_ENTITY = "provider";

    // Initialise services for this controller
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private RequestConfigService $requestConfigService;

    /**
     * ServiceRequestConfigController constructor.
     * Initialise services for this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param RequestConfigService $requestConfigService
     * @param AccessControlService $accessControlService
     */
    public function __construct(ProviderService $providerService, HttpRequestService $httpRequestService,
                                SerializerService $serializerService, ApiService $apiServicesService,
                                RequestConfigService $requestConfigService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        // Initialise services for this controller
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestConfigService = $requestConfigService;
    }

    /**
     * Get list of service request configs function
     * Returns a list of service request configs based on the request query parameters
     *
     * @Route("/list", name="api_get_service_request_config_list", methods={"GET"})
     */
    public function getRequestConfigList(Provider $provider, Request $request)
    {
        $requestConfigArray = [];
        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            self::DEFAULT_ENTITY, $provider, $request->user(),
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ],
            false
        );
        if ($this->isGranted('ROLE_SUPER_ADMIN') ||
            $this->isGranted('ROLE_ADMIN') ||
            $isPermitted
        ) {
            $findRequestConfigs = $this->requestConfigService->findByParams(
                $request->get('service_request_id'),
                $request->get('sort', "item_name"),
                $request->get('order', "asc"),
                (int) $request->get('count', null)
            );
            $requestConfigArray = $this->serializerService->entityArrayToArray($findRequestConfigs, ["list"]);
        }
        return $this->sendSuccessResponse("success", $requestConfigArray);
    }

    /**
     * Get a single service request config
     * Returns a single service request config based on the id passed in the request url
     *
     * @Route("/{serviceRequestConfig}", name="api_get_service_request_config", methods={"GET"})
     * @param ServiceRequestConfig $serviceRequestConfig
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getServiceRequestConfig(Provider $provider, ServiceRequestConfig $serviceRequestConfig)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($serviceRequestConfig, ["single"]));
    }

    /**
     * Create an service request config based on request POST data
     * Returns json success message and service request config data on successful creation
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/create", name="api_create_service_request_config", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createRequestConfig(Provider $provider, ServiceRequest $serviceRequest, Request $request) {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $create = $this->requestConfigService->createRequestConfig(
            $serviceRequest,
            $this->httpRequestService->getRequestData($request, true)
        );

        if(!$create) {
            return $this->sendErrorResponse("Error inserting config item");
        }
        return $this->sendSuccessResponse("Config item inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * Update a service request config based on request POST data
     * Returns json success message and service request config data on successful update
     *
     * Returns error response and message on fail
     * @param Request $request
     * @Route("/{serviceRequestConfig}/update", name="api_update_service_request_config", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateRequestConfig(Provider $provider, ServiceRequest $serviceRequest, ServiceRequestConfig $serviceRequestConfig, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            );
        }
        $update = $this->requestConfigService->updateRequestConfig(
            $serviceRequest, $serviceRequestConfig,
            $this->httpRequestService->getRequestData($request, true));

        if(!$update) {
            return $this->sendErrorResponse("Error updating config item");
        }
        return $this->sendSuccessResponse("Config item updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Delete a service request config based on request POST data
     * Returns json success message and service request config data on successful deletion
     *
     * Returns error response and message on fail
     * @param Request $request
     * @Route("/{serviceRequestConfig}/delete", name="api_delete_service_request_config", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteRequestConfig(Provider $provider, ServiceRequestConfig $serviceRequestConfig, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            );
        }
        $delete = $this->requestConfigService->deleteRequestConfig($serviceRequestConfig);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting config item", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->sendSuccessResponse("Config item deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}
