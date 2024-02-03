<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\CreateProviderRequest;
use App\Http\Requests\Provider\DeleteBatchProvidersRequest;
use App\Http\Requests\Provider\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for provider related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ProviderController extends Controller
{
    private ProviderService $providerService;

    /**
     * ProviderController constructor.
     * @param ProviderService $providerService
     * @param AccessControlService $accessControlService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     */
    public function __construct(
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ProviderService $providerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
    }

    /**
     * Gets a list of providers from the database based on the get request query parameters
     *
     */
    public function getProviderList(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        ) {
            $providers = $this->providerService->getProviderList(
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                $request->get('count', -1)
            );
        } else {
            $providers = $this->providerService->findUserProviders(
                $user,
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                $request->get('count', -1)
            );
        }
        return $this->sendSuccessResponse(
            "success",
            ProviderResource::collection($providers)
        );
    }

    /**
     * Gets a single provider from the database based on the id in the get request url
     *
     */
    public function getProvider(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        return $this->sendSuccessResponse(
            "success",
            new ProviderResource($provider)
        );
    }

    /**
     * Creates a provider in the database based on the post request data
     *
     */
    public function createProvider(CreateProviderRequest $request): \Illuminate\Http\JsonResponse
    {
        $createProvider = $this->providerService->createProvider(
            $request->user(),
            $request->validated()
        );

        if (!$createProvider) {
            return $this->sendErrorResponse("Error inserting provider");
        }
        return $this->sendSuccessResponse(
            "Provider added",
            new ProviderResource($this->providerService->getProviderRepository()->getModel())
        );
    }


    /**
     * Updates a provider in the database based on the post request data
     *
     */
    public function updateProvider(Provider $provider, UpdateProviderRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        $updateProvider = $this->providerService->updateProvider(
            $request->user(),
            $provider,
            $request->validated()
        );

        if (!$updateProvider) {
            return $this->sendErrorResponse("Error updating provider");
        }
        return $this->sendSuccessResponse(
            "Provider updated",
            new ProviderResource($this->providerService->getProviderRepository()->getModel())
        );
    }


    /**
     * Deletes a provider in the database based on the post request data
     *
     */
    public function deleteProvider(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
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
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        $delete = $this->providerService->deleteProvider($provider);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting provider"
            );
        }
        return $this->sendSuccessResponse(
            "Provider deleted."
        );
    }
    public function deleteBatchProviders(
        Provider $provider,
        DeleteBatchProvidersRequest  $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());

        if (!$this->providerService->deleteBatchProvider($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting providers",
            );
        }
        return $this->sendSuccessResponse(
            "Providers deleted.",
        );
    }
}
