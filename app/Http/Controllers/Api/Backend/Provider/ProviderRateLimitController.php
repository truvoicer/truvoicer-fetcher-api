<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\RateLimit\CreateProviderRateLimitRequest;
use App\Http\Requests\Provider\RateLimit\UpdateProviderRateLimitRequest;
use App\Http\Resources\Service\ServiceRequest\ServiceRequestConfigResource;
use App\Http\Resources\ProviderRateLimitResource;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\ProviderRateLimit;
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
class ProviderRateLimitController extends Controller
{

    // Initialise services for this controller
    private RateLimitService $providerRateLimitService;

    /**
     * ServiceRequestConfigController constructor.
     * Initialise services for this controller
     *
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param RateLimitService $providerRateLimitService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        RateLimitService     $providerRateLimitService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        // Initialise services for this controller
        $this->providerRateLimitService = $providerRateLimitService;
    }

    public function getProviderRateLimit(
        Provider $provider,
        ProviderRateLimit $providerRateLimit,
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
            new ProviderRateLimitResource($providerRateLimit)
        );
    }

    /**
     * Create an service request config based on request POST data
     * Returns json success message and service request config data on successful creation
     * Returns error response and message on fail
     *
     */
    public function createProviderRateLimit(
        Provider                          $provider,
        CreateProviderRateLimitRequest $request
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
        $create = $this->providerRateLimitService->createProviderRateLimit(
            $provider,
            $request->all()
        );

        if (!$create) {
            return $this->sendErrorResponse("Error creating rate limit");
        }
        return $this->sendSuccessResponse(
            "Rate Limit created",
            new ProviderRateLimitResource(
                $this->providerRateLimitService->getProviderRateLimitRepository()->getModel()
            )
        );
    }

    /**
     * Update a service request config based on request POST data
     * Returns json success message and service request config data on successful update
     *
     * Returns error response and message on fail
     */
    public function updateProviderRateLimit(
        Provider                          $provider,
        ProviderRateLimit                    $providerRateLimit,
        UpdateProviderRateLimitRequest $request
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
        $update = $this->providerRateLimitService->saveProviderRateLimit(
            $providerRateLimit,
            $request->all()
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating rate limit");
        }
        return $this->sendSuccessResponse(
            "RateLimit updated",
            new ProviderRateLimitResource(
                $this->providerRateLimitService->getProviderRateLimitRepository()->getModel()
            )
        );
    }

    /**
     * Delete a service request config based on request POST data
     * Returns json success message and service request config data on successful deletion
     *
     * Returns error response and message on fail
     */
    public function deleteProviderRateLimit(
        Provider $provider,
        ProviderRateLimit $providerRateLimit,
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
        if (!$this->providerRateLimitService->deleteProviderRateLimit($providerRateLimit)) {
            return $this->sendErrorResponse(
                "Error deleting rate limit"
            );
        }
        return $this->sendSuccessResponse(
            "RateLimit deleted."
        );
    }
}
