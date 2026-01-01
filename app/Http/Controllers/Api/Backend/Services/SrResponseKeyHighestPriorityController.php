<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\SrResponseKey;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use Truvoicer\TfDbReadCore\Services\Permission\PermissionService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class SrResponseKeyHighestPriorityController extends Controller
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
        SrResponseKey $srResponseKey,
        Request       $request
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

        $update = $this->srResponseKeyService->getSrResponseKeyRepository()->setResponseKeyAsHighestPriority(
            $request->user(),
            $serviceRequest,
            $srResponseKey,
        );

        if (!$update) {
            return $this->sendErrorResponse("Error setting sr response key as highest priority");
        }
        return $this->sendSuccessResponse(
            "Sr response key set as highest priority successfully",
        );
    }

}
