<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\RateLimit\CreateSrRateLimitRequest;
use App\Http\Requests\Service\Request\RateLimit\UpdateSrRateLimitRequest;
use App\Http\Resources\SrRateLimitResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrRateLimit;
use App\Services\ApiServices\RateLimitService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for service request config related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class SrRateLimitController extends Controller
{

    // Initialise services for this controller
    private RateLimitService $srRateLimitService;

    /**
     * ServiceRequestConfigController constructor.
     * Initialise services for this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param RateLimitService $srRateLimitService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        RateLimitService     $srRateLimitService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        // Initialise services for this controller
        $this->srRateLimitService = $srRateLimitService;
    }

    public function getServiceRateLimit(
        Provider $provider,
        Sr       $serviceRequest,
        SrRateLimit $srRateLimit,
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
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        return $this->sendSuccessResponse(
            "success",
            new SrRateLimitResource($srRateLimit)
        );
    }

    /**
     * Create an service request config based on request POST data
     * Returns json success message and service request config data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createRequestRateLimit(
        Provider                          $provider,
        Sr                                $serviceRequest,
        CreateSrRateLimitRequest $request
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
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        $create = $this->srRateLimitService->createSrRateLimit(
            $serviceRequest,
            $request->all()
        );

        if (!$create) {
            return $this->sendErrorResponse("Error creating schedule");
        }
        return $this->sendSuccessResponse(
            "RateLimit created",
            new SrRateLimitResource(
                $this->srRateLimitService->getSrRateLimitRepository()->getModel()
            )
        );
    }

    /**
     * Update a service request config based on request POST data
     * Returns json success message and service request config data on successful update
     *
     * Returns error response and message on fail
     */
    public function updateRequestRateLimit(
        Provider                          $provider,
        Sr                                $serviceRequest,
        SrRateLimit                    $srRateLimit,
        UpdateSrRateLimitRequest $request
    ): \Illuminate\Http\JsonResponse
    {
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
        $update = $this->srRateLimitService->saveSrRateLimit(
            $srRateLimit,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating schedule");
        }
        return $this->sendSuccessResponse(
            "RateLimit updated",
            new SrRateLimitResource(
                $this->srRateLimitService->getSrRateLimitRepository()->getModel()
            )
        );
    }

    /**
     * Delete a service request config based on request POST data
     * Returns json success message and service request config data on successful deletion
     *
     * Returns error response and message on fail
     */
    public function deleteRequestRateLimit(
        Provider $provider,
        Sr       $serviceRequest,
        SrRateLimit $srRateLimit,
        Request  $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        if (!$this->srRateLimitService->deleteSrRateLimit($srRateLimit)) {
            return $this->sendErrorResponse(
                "Error deleting schedule"
            );
        }
        return $this->sendSuccessResponse(
            "RateLimit deleted."
        );
    }
}
