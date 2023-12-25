<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionEntities;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\SecurityService;
use App\Services\Tools\SerializerService;
use App\Services\User\UserAdminService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Contains api endpoint functions for admin related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class UserController extends Controller
{
    private UserAdminService $userService;

    /**
     * AdminController constructor.
     * Initialises services used in this controller
     *
     * @param UserAdminService $userService
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        UserAdminService $userService,
        SerializerService $serializerService,
        HttpRequestService $httpRequestService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->userService = $userService;
    }

    public function getSessionUserDetail(Request $request)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($request->user())
        );
    }

    public function getProtectedEntitiesList()
    {
        return $this->sendSuccessResponse(
            "success",
            PermissionEntities::PROTECTED_ENTITIES
        );
    }

    public function getUserEntityPermissionList(string $entity, Request $request)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permission list",
            $this->serializerService->entityArrayToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList(
                    $entity,
                    $request->user()
                ),
                ["list"]
            )
        );
    }

    public function getUserEntityPermission(string $entity, int $id, Request $request)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permissions",
            $this->serializerService->entityToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermission(
                    $entity,
                    $id,
                    $request->user()
                ),
                ["list"]
            )
        );
    }

    public function getSessionUserApiToken(PersonalAccessToken $personalAccessToken, Request $request)
    {
        if (!$this->userService->apiTokenBelongsToUser($request->user(), $personalAccessToken)) {
            return $this->sendErrorResponse(
                "Operation not permitted"
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($personalAccessToken)
        );
    }

    public function getSessionUserApiTokenList(Request $request)
    {
        $getApiTokens = $this->userService->findApiTokensByParams(
            $request->user(),
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray(
                array_filter($getApiTokens, function ($token, $key) {
                    return $token->getType() === "user";
                }, ARRAY_FILTER_USE_BOTH)
            )
        );
    }

    public function generateSessionUserApiToken(Request $request)
    {
        $user = $request->user();
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray(
                $this->userService->createUserToken(
                    $request->user(),
                    $user->getRole()->id
                )
            )
        );
    }

    public function deleteSessionUserApiToken(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $apiToken = $this->userService->getApiTokenById($requestData['item_id']);

        if (!$this->userService->apiTokenBelongsToUser($request->user(), $apiToken)) {
            return $this->sendErrorResponse(
                "Operation not permitted"
            );
        }
        $delete = $this->userService->deleteApiToken($apiToken);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting api token",
                $this->serializerService->entityToArray($delete, ['main'])
            );
        }
        return $this->sendSuccessResponse(
            "Api Token deleted.",
            $this->serializerService->entityToArray($delete, ['main'])
        );
    }

    public function updateSessionUser(Request $request)
    {
        $update = $this->userService->updateUser(
            $request->user(),
            $this->httpRequestService->getRequestData($request, true)
        );
        if (!$update) {
            return $this->sendErrorResponse("Error updating user");
        }
        return $this->sendSuccessResponse(
            "User updated",
            $this->serializerService->entityToArray($update, ['main'])
        );
    }
}
