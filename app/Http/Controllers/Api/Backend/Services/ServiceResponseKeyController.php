<?php
namespace App\Controller\Api\Backend\Services;

use App\Controller\Api\BaseController;
use App\Entity\Service;
use App\Entity\ServiceResponseKey;
use App\Service\ApiServices\ApiService;
use App\Service\Permission\AccessControlService;
use App\Service\Tools\HttpRequestService;
use App\Service\Provider\ProviderService;
use App\Service\ApiServices\ResponseKeysService;
use App\Service\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains Api endpoint functions for api service response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/service/{service}/response/key")
 */
class ServiceResponseKeyController extends BaseController
{
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private ResponseKeysService $responseKeysService;

    /**
     * ServiceResponseKeyController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param ResponseKeysService $responseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(ProviderService $providerService, HttpRequestService $httpRequestService,
                                SerializerService $serializerService, ApiService $apiServicesService,
                                ResponseKeysService $responseKeysService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->responseKeysService = $responseKeysService;
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     * @Route("/list", name="api_get_service_response_key_list", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getServiceResponseKeyList(Service $service, Request $request)
    {
        $data = $request->query->all();
        if (isset($data["service_id"])) {
            $responseKeys = $this->responseKeysService->getResponseKeysByServiceId($data['service_id']);
        } elseif (isset($data["service_name"])) {
            $responseKeys = $this->responseKeysService->getResponseKeysByServiceName($data['service_name']);
        }
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray($responseKeys, ["list"]));
    }

    /**
     * Get a single service response key
     * Returns a single service response key based on the id passed in the request url
     *
     * @Route("/{id}", name="api_get_service_response_key", methods={"GET"})
     * @param ServiceResponseKey $serviceResponseKey
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getServiceResponseKey(ServiceResponseKey $serviceResponseKey)
    {
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityToArray($serviceResponseKey, ["single"]));
    }

    /**
     * Create an api service response key based on request POST data
     * Returns json success message and api service response key data on successful creation
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/create", name="api_create_service_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createServiceResponseKey(Request $request) {
        $create = $this->responseKeysService->createServiceResponseKeys(
            $this->httpRequestService->getRequestData($request, true));

        if(!$create) {
            return $this->jsonResponseFail("Error inserting service response key");
        }
        return $this->jsonResponseSuccess("Service response key inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * Update an api service response key based on request POST data
     * Returns json success message and api service response key data on successful update
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceResponseKey}/update", name="api_update_service_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateServiceResponseKey(ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $update = $this->responseKeysService->updateServiceResponseKeys(
            $serviceResponseKey,
            $this->httpRequestService->getRequestData($request, true));

        if(!$update) {
            return $this->jsonResponseFail("Error updating service response key");
        }
        return $this->jsonResponseSuccess("Service response key updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Delete an api service response key based on request POST data
     * Returns json success message and api service response key data on successful delete
     * Returns error response and message on fail
     *
     * @param Request $request
     * @Route("/{serviceResponseKey}/delete", name="api_delete_service_response_key", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteServiceResponseKey(ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $delete = $this->responseKeysService->deleteServiceResponseKey($serviceResponseKey);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting service response key", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->jsonResponseSuccess("Response key service deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}
