<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\ResponseKey\StoreSrResponseKeySrRequest;
use App\Http\Requests\Service\Request\ResponseKey\UpdateSrResponseKeySrRequest;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeySrsCollection;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeySrsResource;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeyWithServiceCollection;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrResponseKeySr;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;

/**
 * Contains Api endpoint functions for api service request response keys related operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class SrResponseKeySrController extends Controller
{

    public function __construct(
        private SrResponseKeyService $srResponseKeyService,
        private SrResponseKeySrRepository $srResponseKeySrRepository,
    ) {
        parent::__construct();
    }

    public function index(Provider $provider, Sr $serviceRequest, Request $request)
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
        if (!$this->srResponseKeyService->validateSrResponseKeys($serviceRequest, true)) {
            return $this->sendErrorResponse(
                "Error validating response keys",
                [],
                $this->srResponseKeySrRepository->getErrors()
            );
        }
        $this->srResponseKeySrRepository->setQuery(
            $serviceRequest->srResponseKeySrs()
        );
        $this->srResponseKeySrRepository->setPagination(
            $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
        );
        // $this->srResponseKeySrRepository->setSortField(
        //     $request->get('sort', "name")
        // );
        $this->srResponseKeySrRepository->setOrderDir(
            $request->get('order', "asc")
        );

        $this->srResponseKeySrRepository->setPerPage(
            $request->get('count', -1)
        );
        return $this->sendSuccessResponse(
            "success",
            new SrResponseKeySrsCollection(
                $this->srResponseKeySrRepository->findMany()
            )
        );
    }


    public function show(
        Provider $provider,
        Sr $serviceRequest,
        SrResponseKeySr $srResponseKeySr,
        Request $request
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
        return new SrResponseKeySrsResource($srResponseKeySr);
    }

    public function store(
        Provider $provider,
        Sr $serviceRequest,
        StoreSrResponseKeySrRequest $request
    ) {

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
        $responseKeyId = $request->validated('response_key_id');
        $srResponseKey = $this->srResponseKeyService->getSrResponseKeyRepository()->findById($responseKeyId);
        if (!$srResponseKey) {
            return $this->sendErrorResponse(
                'There was an error finding the response key',
            );
        }
        $store = $this->srResponseKeyService->saveSrResponseKeySrs(
            $request->user(),
            $srResponseKey,
            $request->validated()
        );
        if (!$store) {
            return $this->sendErrorResponse(
                'There was an error storing the response key sr\'s',
            );
        }
        return $this->sendSuccessResponse(
            "success"
        );
    }

    public function update(
        Provider $provider,
        Sr $serviceRequest,
        SrResponseKeySr $srResponseKeySr,
        UpdateSrResponseKeySrRequest $request
    ) {
        $user = $request->user();
        $this->setAccessControlUser($user);
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
        $update = $this->srResponseKeyService->saveSrResponseKeySrs(
            $user,
            $srResponseKey,
            $request->validated()
        );
        if (!$update) {
            return $this->sendErrorResponse(
                'There was an error storing the response key sr\'s',
            );
        }
        return $this->sendSuccessResponse(
            "success"
        );
    }
}
