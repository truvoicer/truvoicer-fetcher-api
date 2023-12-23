<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceResponseKeyController extends Controller
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
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        ResponseKeysService $responseKeysService,
        AccessControlService $accessControlService,
        Request $request
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->responseKeysService = $responseKeysService;
    }

    /**
     * Get a list of response keys.
     * Returns a list of response keys based on the request query parameters
     *
     */
    public function getServiceResponseKeyList(Service $service, Request $request)
    {
        $data = $request->query->all();
        if (isset($data["service_id"])) {
            $responseKeys = $this->responseKeysService->getResponseKeysByServiceId($data['service_id']);
        } elseif (isset($data["name"])) {
            $responseKeys = $this->responseKeysService->getResponseKeysByServiceName($data['name']);
        } else {
            return $this->sendErrorResponse("Error service id or name not in request",);
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray($responseKeys, ["list"])
        );
    }

    /**
     * Get a single service response key
     * Returns a single service response key based on the id passed in the request url
     *
     */
    public function getServiceResponseKey(ServiceResponseKey $serviceResponseKey)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($serviceResponseKey, ["single"])
        );
    }

    /**
     * Create an api service response key based on request POST data
     * Returns json success message and api service response key data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createServiceResponseKey(Request $request)
    {
        $create = $this->responseKeysService->createServiceResponseKeys(
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$create) {
            return $this->sendErrorResponse("Error inserting service response key");
        }
        return $this->sendSuccessResponse(
            "Service response key inserted",
            $this->serializerService->entityToArray($create, ['single'])
        );
    }

    /**
     * Update an api service response key based on request POST data
     * Returns json success message and api service response key data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateServiceResponseKey(ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $update = $this->responseKeysService->updateServiceResponseKeys(
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
     * Delete an api service response key based on request POST data
     * Returns json success message and api service response key data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteServiceResponseKey(ServiceResponseKey $serviceResponseKey, Request $request)
    {
        $delete = $this->responseKeysService->deleteServiceResponseKey($serviceResponseKey);
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
