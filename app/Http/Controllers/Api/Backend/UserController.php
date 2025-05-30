<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\CreateUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Resources\PersonalAccessTokenCollection;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Http\Resources\UserResource;
use App\Services\ApiServices\ServiceRequests\SrService;
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

    public function __construct(
        private UserAdminService $userService,
    ) {
        parent::__construct();
    }

    public function getSessionUserDetail(Request $request)
    {
        return $this->sendSuccessResponse(
            "success",
            new UserResource($request->user())
        );
    }

    public function getProtectedEntitiesList()
    {
        return $this->sendSuccessResponse(
            "success",
            PermissionEntities::PROTECTED_ENTITIES
        );
    }

    public function getUserEntityPermissionList(string $entity, int $id, Request $request)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permission list",
            $this->serializerService->entityArrayToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList(
                    $entity,
                    $id,
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

    public function getSessionUserApiToken(Request $request)
    {
        return $this->sendSuccessResponse(
            "success",
            new PersonalAccessTokenResource($request->user()->currentAccessToken())
        );
    }

    public function getSessionUserApiTokenList(Request $request)
    {
        $getApiTokens = $this->userService->findApiTokensByParams(
            $request->user(),
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            $request->get('count')
        );
        return $this->sendSuccessResponse(
            "success",
            new PersonalAccessTokenCollection($getApiTokens)
        );
    }

    public function generateSessionUserApiToken(Request $request)
    {
        return $this->sendSuccessResponse(
            "success",
            new PersonalAccessTokenResource(
                $this->userService->createUserToken(
                    $request->user(),
                )
            )
        );
    }

    public function deleteSessionUserApiToken(PersonalAccessToken $personalAccessToken, Request $request)
    {
        $delete = $this->userService->deleteApiToken($personalAccessToken);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting api token",
            );
        }
        return $this->sendSuccessResponse(
            "Api Token deleted.",
        );
    }

    public function updateSessionUser(UpdateUserRequest $request)
    {
        $this->accessControlService->setUser($request->user());
        $roles = [];
        if (
            $this->accessControlService->inAdminGroup() &&
            $request->has('role_id')
        ) {
            $roles = $request->get('roles');
        }
        $update = $this->userService->updateUser(
            $request->user(),
            $request->all(),
            $roles
        );
        if (!$update) {
            return $this->sendErrorResponse("Error updating user");
        }
        return $this->sendSuccessResponse(
            "User updated",
            new UserResource(
                $this->userService->getUserRepository()->getModel()
            )
        );
    }
}
