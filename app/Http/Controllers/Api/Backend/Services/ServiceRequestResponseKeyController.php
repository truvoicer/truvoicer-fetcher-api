<?php
namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Entity\Provider;
use App\Entity\ServiceRequest;
use App\Entity\ServiceRequestResponseKey;
use App\Entity\ServiceResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\RequestResponseKeysService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains Api endpoint functions for api service request response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/provider/{provider}/service/request/{id}/response/key")
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
    public function __construct(ProviderService $providerService, HttpRequestService $httpRequestService,
                                SerializerService $serializerService, ApiService $apiServicesService,
                                RequestResponseKeysService $requestResponseKeysService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->requestResponseKeysService = $requestResponseKeysService;
    }

    /**
     * Get a list of service request response keys.
     * Returns a list of service request response keys based on the request query parameters
     *
     * @Route("/list", name="api_get_request_response_key_list", methods={"GET"})
     * @param ServiceRequest $serviceRequest
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getRequestResponseKeyList(Provider $provider, ServiceRequest $serviceRequest, Request $request)
    {
        $requestResponseKeysArray = [];
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
            $responseKeys = $this->requestResponseKeysService->getRequestResponseKeys(
                $serviceRequest,
                $request->get('sort', "key_name"),
                $request->get('order', "asc"),
                (int) $request->get('count', null)
            );
            $requestResponseKeysArray = $this->serializerService->entityArrayToArray($responseKeys, ["response_key"]);
        }
        return $this->sendSuccessResponse("success", $requestResponseKeysArray);
    }

    /**
     * Get a single service request response key
     * Returns a single service request response key based on the id passed in the request url
     *
     * @Route("/{serviceResponseKey}", name="api_get_request_response_key", methods={"GET"})
     * @param ServiceRequest $serviceRequest
     * @param ServiceResponseKey $serviceResponseKey
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getRequestResponseKey(Provider $provider, ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey)
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
        $getRequestResponseKey = $this->requestResponseKeysService->getRequestResponseKeyObjectById($serviceRequest, $serviceResponseKey);
        return $this->sendSuccessResponse("success", $this->serializerService->entityToArray($getRequestResponseKey, ["response_key"]));
    }

    /**
     * Create an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful creation
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceResponseKey}/create", name="api_create_request_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createRequestResponseKey(Provider $provider, ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, Request $request) {
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
        $requestData = $this->httpRequestService->getRequestData($request);
        $create = $this->requestResponseKeysService->createRequestResponseKey($serviceRequest, $serviceResponseKey, $requestData->data);
        if(!$create) {
            return $this->sendErrorResponse("Error adding response key.");
        }
        return $this->sendSuccessResponse("Successfully added response key.",
            $this->serializerService->entityToArray($create, ["single"]));
    }

    /**
     * Update an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful update
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceResponseKey}/update", name="api_update_request_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateRequestResponseKey(Provider $provider, ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, Request $request)
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
        $update = $this->requestResponseKeysService->updateRequestResponseKey(
            $serviceRequest, $serviceResponseKey,
            $this->httpRequestService->getRequestData($request, true));

        if(!$update) {
            return $this->sendErrorResponse("Error updating service response key");
        }
        return $this->sendSuccessResponse("Service response key updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Delete  an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful delete
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceResponseKey}/delete", name="api_delete_request_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteRequestResponseKey(Provider $provider, ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, Request $request)
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
        $delete = $this->requestResponseKeysService->deleteRequestResponseKey($serviceRequest, $serviceResponseKey);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting service response key", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->sendSuccessResponse("Response key service deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}
