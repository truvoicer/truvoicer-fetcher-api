<?php

namespace App\Http\Controllers\Api\Backend\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Request\ResponseKey\StoreSrResponseKeySrRequest;
use App\Http\Requests\Service\Request\ResponseKey\UpdateSrResponseKeySrRequest;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeySrsCollection;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeySrsResource;
use App\Http\Resources\Service\ServiceRequest\SrResponseKeyWithServiceCollection;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SrResponseKeySr;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeySrRepository;
use Truvoicer\TruFetcherGet\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use Truvoicer\TruFetcherGet\Services\Permission\PermissionService;
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

        $srResponseKeyId = $request->validated('sr_response_key_id', null);

        if (empty($srResponseKeyId)) {
            return $this->sendErrorResponse(
                'The sr_response_key_id field is required',
            );
        }

        $srResponseKey = $this->srResponseKeyService->getSrResponseKeyRepository()->findById(
            $srResponseKeyId
        );
        if (!$srResponseKey) {
            return $this->sendErrorResponse(
                'There was an error finding the response key',
            );
        }

        $store = $this->srResponseKeyService->saveSrResponseKeySrs(
            $request->user(),
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

        $requestData = $request->validated();
        if (!empty($requestData['sr_response_key_id'])) {

            $srResponseKey = $this->srResponseKeyService->getSrResponseKeyRepository()->findById(
                $requestData['sr_response_key_id']
            );
            if (!$srResponseKey) {
                return $this->sendErrorResponse(
                    'There was an error finding the response key',
                );
            }
        }
        $store = $this->srResponseKeyService->updateSrResponseKeySr(
            $request->user(),
            $srResponseKeySr,
            $requestData
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

    public function destroy(
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
                    PermissionService::PERMISSION_DELETE,
                ]
            )
        ) {
            return $this->sendErrorResponse("Access denied");
        }
        if ($srResponseKeySr->delete()) {
            return $this->sendSuccessResponse("Successfully deleted response key sr");
        }
        return $this->sendErrorResponse("There was an error deleting the response key sr");
    }
}
