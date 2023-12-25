<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceController extends Controller
{
    private ProviderService $providerService;   // Initialise provider service
    private ApiService $apiServicesService;     // Initialise api services service

    /**
     * ServiceController constructor.
     * Initialises services used in this controller
     *
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ApiService $apiServicesService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        ProviderService $providerService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ApiService $apiServicesService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;   //Initialise provider service
        $this->apiServicesService = $apiServicesService;   //Initialise api services service
    }

    /**
     * Get service list function
     * returns a list of api services based on the request query parameters
     *
     */
    public function getServices(Request $request): \Illuminate\Http\JsonResponse
    {
        $getServices = $this->apiServicesService->findByParams(
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray($getServices, ["list"])
        );
    }

    /**
     * Get a single api service
     * Returns a single api service based on the id passed in the request url
     *
     */
    public function getService(Service $service): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($service, ["single"])
        );
    }

    /**
     * Create an api service based on request POST data
     * Returns json success message and api service data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createService(Request $request): \Illuminate\Http\JsonResponse
    {
        $create = $this->apiServicesService->createService(
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$create) {
            return $this->sendErrorResponse("Error inserting service");
        }
        return $this->sendSuccessResponse(
            "Service inserted",
            $this->serializerService->entityToArray($create, ['single'])
        );
    }

    /**
     * Updates an api service based on request POST data
     * Returns json success message and api service data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateService(Service $service, Request $request): \Illuminate\Http\JsonResponse
    {
        $update = $this->apiServicesService->updateService(
            $service,
            $this->httpRequestService->getRequestData($request, true)
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service");
        }
        return $this->sendSuccessResponse(
            "Service updated",
            $this->serializerService->entityToArray($update, ['single'])
        );
    }

    /**
     * Delete an api service based on request POST data
     * Returns json success message and api service data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteService(Service $service, Request $request): \Illuminate\Http\JsonResponse
    {
        $delete = $this->apiServicesService->deleteService($service);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting service",
                $this->serializerService->entityToArray($delete, ['single'])
            );
        }
        return $this->sendSuccessResponse(
            "Service deleted.",
            $this->serializerService->entityToArray($delete, ['single'])
        );
    }
}
