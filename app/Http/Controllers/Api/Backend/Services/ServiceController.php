<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\CreateSRequest;
use App\Http\Requests\Service\DeleteBatchSRequest;
use App\Http\Requests\Service\UpdateSRequest;
use App\Http\Resources\ProviderCollection;
use App\Http\Resources\Service\ServiceCollection;
use App\Http\Resources\Service\ServiceResource;
use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
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
    /**
     * ServiceController constructor.
     * Initialises services used in this controller
     *
     * @param ApiService $apiServicesService
     */
    public function __construct(
        private ApiService $apiServicesService
    ) {
        parent::__construct();

    }

    /**
     * Get service list function
     * returns a list of api services based on the request query parameters
     *
     */
    public function getServices(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if ($this->accessControlService->inAdminGroup()) {
            $getServices = $this->apiServicesService->findByParams(
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                $request->get('count', -1),
                $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
            );
        } else {

            $this->apiServicesService->getServiceRepository()->setOrderDir($request->get('order', "asc"));
            $this->apiServicesService->getServiceRepository()->setSortField($request->get('sort', "name"));
            $this->apiServicesService->getServiceRepository()->setLimit($request->get('count', -1));

            $getServices = $this->apiServicesService->findUserServices(
                $request->user(),
                $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
            );
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceCollection($getServices)
        );
    }
    public function getServiceProviders(S $service, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        return $this->sendSuccessResponse(
            "success",
            new ProviderCollection($service->providers()->distinct()->get())
        );
    }

    /**
     * Get a single api service
     * Returns a single api service based on the id passed in the request url
     *
     */
    public function getService(S $service, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $service
        );
    }

    /**
     * Create an api service based on request POST data
     * Returns json success message and api service data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createService(CreateSRequest $request): \Illuminate\Http\JsonResponse
    {
        $create = $this->apiServicesService->createService(
            $request->user(),
            $request->all()
        );

        if (!$create) {
            return $this->sendErrorResponse("Error inserting service");
        }
        return $this->sendSuccessResponse(
            "Service inserted",
            new ServiceResource(
                $this->apiServicesService->getServiceRepository()->getModel()
            )
        );
    }

    /**
     * Updates an api service based on request POST data
     * Returns json success message and api service data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateService(S $service, UpdateSRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        $update = $this->apiServicesService->updateService(
            $service,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating service");
        }
        return $this->sendSuccessResponse(
            "Service updated",
            new ServiceResource(
                $this->apiServicesService->getServiceRepository()->getModel()
            )
        );
    }

    /**
     * Delete an api service based on request POST data
     * Returns json success message and api service data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteService(S $service, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $service,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            )
        ) {
            return $this->sendErrorResponse(
                "You do not have permission to view this service",
            );
        }
        $delete = $this->apiServicesService->deleteService($service);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting service"
            );
        }
        return $this->sendSuccessResponse(
            "Service deleted."
        );
    }
    public function deleteBatch(
        DeleteBatchSRequest $request
    ): \Illuminate\Http\JsonResponse
    {
        if (!$this->apiServicesService->deleteBatch($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting services",
            );
        }
        return $this->sendSuccessResponse(
            "Services deleted.",
        );
    }
}
