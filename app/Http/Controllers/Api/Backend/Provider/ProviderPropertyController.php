<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\DeleteBatchPropertyRequest;
use App\Http\Requests\Provider\Property\SaveProviderPropertyRequest;
use App\Http\Resources\PropertyCollection;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\ProviderPropertyCollection;
use App\Models\Property;
use App\Models\Provider;
use App\Repositories\ProviderRepository;
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

    private ProviderService $providerService;

    /**
     * ProviderController constructor.
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ProviderService $providerService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService   $httpRequestService,
        SerializerService    $serializerService,
        ProviderService      $providerService,
        AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
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
            new ProviderPropertyCollection($getProviderProps)
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
            new PropertyResource(
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

        $create = $this->providerService->createProviderProperty($provider, $property, $request->get('value'));
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
