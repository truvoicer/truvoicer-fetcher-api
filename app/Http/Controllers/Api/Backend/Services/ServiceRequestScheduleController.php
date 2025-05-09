<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\RateLimit\CreateSrRateLimitRequest;
use App\Http\Requests\Service\Request\Schedule\UpdateSrScheduleRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestConfigResource;
use App\Http\Resources\SrScheduleResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
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
class ServiceRequestScheduleController extends Controller
{

    public function __construct(
        private SrScheduleService      $srScheduleService,
    ) {
        parent::__construct();
    }

    public function getServiceSchedule(
        Provider $provider,
        Sr       $serviceRequest,
        SrSchedule $srSchedule,
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
            new SrScheduleResource($srSchedule)
        );
    }

    /**
     * Create an service request config based on request POST data
     * Returns json success message and service request config data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createRequestSchedule(
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
        $create = $this->srScheduleService->createSrSchedule(
            $request->user(),
            $serviceRequest,
            $request->all()
        );

        if (!$create) {
            return $this->sendErrorResponse("Error creating schedule");
        }
        return $this->sendSuccessResponse(
            "Schedule created",
            new ServiceRequestConfigResource(
                $this->srScheduleService->getSrScheduleRepository()->getModel()
            )
        );
    }

    /**
     * Update a service request config based on request POST data
     * Returns json success message and service request config data on successful update
     *
     * Returns error response and message on fail
     */
    public function updateRequestSchedule(
        Provider                          $provider,
        Sr                                $serviceRequest,
        SrSchedule                    $srSchedule,
        UpdateSrScheduleRequest $request
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
        $update = $this->srScheduleService->saveSrSchedule(
            $request->user(),
            $srSchedule,
            $request->validated()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating schedule");
        }
        return $this->sendSuccessResponse(
            "Schedule updated",
            new ServiceRequestConfigResource(
                $this->srScheduleService->getSrScheduleRepository()->getModel()
            )
        );
    }

    /**
     * Delete a service request config based on request POST data
     * Returns json success message and service request config data on successful deletion
     *
     * Returns error response and message on fail
     */
    public function deleteRequestSchedule(
        Provider $provider,
        Sr       $serviceRequest,
        SrSchedule $srSchedule,
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
        if (!$this->srScheduleService->deleteSrSchedule($srSchedule)) {
            return $this->sendErrorResponse(
                "Error deleting schedule"
            );
        }
        return $this->sendSuccessResponse(
            "Schedule deleted."
        );
    }
}
