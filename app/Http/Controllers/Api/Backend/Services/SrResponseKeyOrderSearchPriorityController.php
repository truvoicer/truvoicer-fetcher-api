<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\ResponseKey\UpdateSrResponseKeySearchPriorityOrderRequest;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class SrResponseKeyOrderSearchPriorityController extends Controller
{

    public function __construct(
        private SrResponseKeyService $srResponseKeyService,
    ) {
        parent::__construct();
    }

    /**
     * Update an api service request response key based on request POST data
     * Returns json success message and api service request response key data on successful update
     * Returns error response and message on fail
     *
     */
    public function __invoke(
        Provider      $provider,
        Sr            $serviceRequest,
        UpdateSrResponseKeySearchPriorityOrderRequest       $request
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

        $update = $this->srResponseKeyService->getSrResponseKeyRepository()->reorderSrResponseKeys(
            $request->validated('ids', [])
        );

        if (!$update) {
            return $this->sendErrorResponse("Error updating sr response key search priority order");
        }
        return $this->sendSuccessResponse(
            "Sr response key search priority order updated successfully",
        );
    }
}
