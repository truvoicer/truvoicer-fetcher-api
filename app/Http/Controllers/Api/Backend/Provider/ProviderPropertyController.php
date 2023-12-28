<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
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

    private PropertyService $propertyService;
    private ProviderService $providerService;

    /**
     * ProviderController constructor.
     * @param PropertyService $propertyService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ProviderService $providerService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        PropertyService $propertyService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ProviderService $providerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->propertyService = $propertyService;
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
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        $getProviderProps = $this->providerService->getProviderProperties(
            $provider->id,
            $request->get('sort', "property_name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse("success", $getProviderProps);
    }

    /**
     * Gets a single related provider property based on
     * the provider id and property id in the url
     *
     */
    public function getProviderProperty(
        Provider $provider,
        Property $property
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->providerService->getProviderPropertyObjectById($provider, $property)
        );
    }

    /**
     * Creates a related provider property in the database based on the post request data
     * Required data request data fields:
     * - provider_id
     * - property_id
     */
    public function createProviderProperty(Provider $provider, Property $property, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $create = $this->providerService->createProviderProperty($provider, $property, $request->get('value'));
        if (!$create) {
            return $this->sendErrorResponse("Error adding provider property.");
        }
        return $this->sendSuccessResponse(
            "Successfully added provider property.",
            $this->serializerService->entityToArray($create)
        );
    }

    /**
     * Updates a related provider property in the database based on the post request data
     * Required data request data fields:
     * - provider_id
     * - property_id
     *
     */
    public function updateProviderProperty(
        Provider $provider,
        Property $property
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $data = $this->httpRequestService->getRequestData($request, true);
        $update = $this->providerService->updateProviderProperty($provider, $property, $data);

        if (!$update) {
            return $this->sendErrorResponse("Error updating provider property");
        }
        return $this->sendSuccessResponse(
            "Provider property updated",
            $this->serializerService->entityToArray($update)
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
        Property $property
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user(
            )->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            );
        }
        $delete = $this->providerService->deleteProviderProperty($provider, $property);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting provider property value",
                $this->serializerService->entityToArray($delete, ['main'])
            );
        }
        return $this->sendSuccessResponse(
            "Provider property value deleted.",
            $this->serializerService->entityToArray($delete, ['main'])
        );
    }
}
