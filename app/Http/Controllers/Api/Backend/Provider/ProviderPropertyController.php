<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\DeleteBatchPropertyRequest;
use App\Http\Requests\Provider\Property\SaveProviderPropertyRequest;
use App\Http\Resources\PropertyCollection;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\PropertyWithProviderPropertyCollection;
use App\Http\Resources\PropertyWithProviderPropertyResource;
use Truvoicer\TruFetcherGet\Models\Property;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Repositories\ProviderRepository;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
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
class ProviderPropertyController extends Controller
{


    /**
     * ProviderController constructor.
     * @param ProviderService $providerService
     */
    public function __construct(
        private ProviderService      $providerService
    )
    {
        parent::__construct();
    }

    /**
     * Gets a list of related provider property objects based on the get request
     * query parameters
     *
     */
    public function getProviderPropertyList(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
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
        $this->providerService->getProviderPropertyRepository()->setSortField($request->get('sort', "name"));
        $this->providerService->getProviderPropertyRepository()->setOrderDir($request->get('order', "asc"));
        $this->providerService->getProviderPropertyRepository()->setLimit((int)$request->get('count', -1));
        $getProviderProps = $this->providerService->getProviderProperties(
            $provider
        );
        return $this->sendSuccessResponse(
            "success",
            new PropertyWithProviderPropertyCollection($getProviderProps)
        );
    }

    /**
     * Gets a single related provider property based on
     * the provider id and property id in the url
     *
     */
    public function getProviderProperty(
        Provider $provider,
        Property $property,
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
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        return $this->sendSuccessResponse(
            "success",
            new PropertyWithProviderPropertyResource(
                $this->providerService->getProviderProperty($provider, $property)
            )
        );
    }

    /**
     * Creates a related provider property in the database based on the post request data
     * Required data request data fields:
     * - provider_id
     * - property_id
     */
    public function saveProviderProperty(Provider $provider, Property $property, SaveProviderPropertyRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }

        $create = $this->providerService->createProviderProperty(
            $request->user(),
            $provider,
            $property,
            $request->validated()
        );
        if (!$create) {
            return $this->sendErrorResponse("Error adding provider property.");
        }
        return $this->sendSuccessResponse(
            "Successfully added provider property.",
            new PropertyResource(
                $this->providerService->getProviderProperty($provider, $property)
            )
        );
    }

    /**
     * Deletes a related provider property in the database based on the post request data
     * Required data request data fields:
     * - item_id (property_id)
     * - extra->provider_id
     *
     */
    public function deleteProviderProperty(
        Provider $provider,
        Property $property,
        Request  $request
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
            return $this->sendErrorResponse("Access control: operation not permitted");
        }

        $delete = $this->providerService->deleteProviderProperty($provider, $property);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting provider property value"
            );
        }
        return $this->sendSuccessResponse(
            "Provider property value deleted."
        );
    }
    public function deleteBatch(
        Provider $provider,
        DeleteBatchPropertyRequest $request
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

        if (!$this->providerService->deleteBatchProviderProperty($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting provider properties",
            );
        }
        return $this->sendSuccessResponse(
            "Provider properties deleted.",
        );
    }
}
