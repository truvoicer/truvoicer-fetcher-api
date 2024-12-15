<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\Parameter\CreateServiceRequestParameterRequest;
use App\Http\Requests\Service\Request\Parameter\DeleteBatchSrParameterRequest;
use App\Http\Requests\Service\Request\Parameter\UpdateServiceRequestParameterRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestParameterCollection;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestParameterResource;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrParameter;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request parameter related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ServiceRequestParameterController extends Controller
{
    private SrParametersService $requestParametersService;

    /**
     * ServiceRequestParameterController constructor.
     * Initialises services used in this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param SrParametersService $requestParametersService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        SrParametersService  $requestParametersService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService);
        $this->requestParametersService = $requestParametersService;
    }

    /**
     * Get a list of service request parameters.
     * Returns a list of service request parameters based on the request query parameters
     *
     */
    public function getServiceRequestParameterList(Provider $provider, Sr $serviceRequest, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $findRequestParameters = $this->requestParametersService->findByParams(
            $serviceRequest,
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );

        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestParameterCollection(
                $findRequestParameters
            )
        );
    }

    /**
     * Get a single service request parameter
     * Returns a single service request parameter based on the id passed in the request url
     *
     */
    public function getSingleServiceRequestParameters(
        Provider $provider,
        Sr       $serviceRequest,
        Request  $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestResource($serviceRequest)
        );
    }

    /**
     * Get a single service request parameter
     * Returns a single service request parameter based on the id passed in the request url
     *
     */
    public function getServiceRequestParameter(
        Provider    $provider,
        Sr          $serviceRequest,
        SrParameter $serviceRequestParameter,
        Request     $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new ServiceRequestParameterResource($serviceRequestParameter)
        );
    }

    /**
     * Create an api service request parameter based on request POST data
     * Returns json success message and api service request data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createServiceRequestParameter(Provider $provider, Sr $serviceRequest, CreateServiceRequestParameterRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->requestParametersService->createRequestParameter($serviceRequest, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error inserting parameter");
        }
        return $this->sendSuccessResponse(
            "Parameter inserted",
            new ServiceRequestParameterResource(
                $this->requestParametersService->getRequestParametersRepo()->getModel()
            )
        );
    }

    /**
     * Update an api service request parameter based on request POST data
     * Returns json success message and api service request parameter data on successful update
     * Returns error response and message on fail
     *
     */
    public function updateServiceRequestParameter(
        Provider                             $provider,
        Sr                                   $serviceRequest,
        SrParameter                          $serviceRequestParameter,
        UpdateServiceRequestParameterRequest $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $update = $this->requestParametersService->updateRequestParameter(
            $serviceRequestParameter,
            $request->all()
        );
        if (!$update) {
            return $this->sendErrorResponse("Error updating parameter");
        }
        return $this->sendSuccessResponse(
            "Parameter updated",
            new ServiceRequestParameterResource(
                $this->requestParametersService->getRequestParametersRepo()->getModel()
            )
        );
    }

    /**
     * Delete an api service request parameter based on request POST data
     * Returns json success message and api service request parameter data on successful delete
     * Returns error response and message on fail
     *
     */
    public function deleteServiceRequestParameter(
        Provider    $provider,
        Sr          $serviceRequest,
        SrParameter $serviceRequestParameter,
        Request     $request
    ) {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        if (!$this->requestParametersService->deleteRequestParameter($serviceRequestParameter)) {
            return $this->sendErrorResponse(
                "Error deleting parameter"
            );
        }
        return $this->sendSuccessResponse(
            "Parameter deleted."
        );
    }
    public function deleteBatch(
        Provider      $provider,
        Sr            $serviceRequest,
        DeleteBatchSrParameterRequest $request
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

        if (!$this->requestParametersService->deleteBatch($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting service request parameters",
            );
        }
        return $this->sendSuccessResponse(
            "Service request parameters deleted.",
        );
    }
}
