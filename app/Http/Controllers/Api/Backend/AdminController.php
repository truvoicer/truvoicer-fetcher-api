<?php
namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
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
class AdminController extends Controller
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
    public function __construct(UserAdminService $userService, SerializerService $serializerService,
                                HttpRequestService $httpRequestService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->userService = $userService;
    }

    /**
     * Gets a list of users based on the request query data
     * Returns successful json response and array of user objects
     *
     */
    public function getUsersList(Request $request)
    {
        $getUsers = $this->userService->findByParams(
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );
        return $this->sendSuccessResponse("success", $this->serializerService->entityArrayToArray($getUsers));
    }

    /**
     * Gets a single user based on the id in the request url
     *
     */
    public function getSingleUser(User $user)
    {
        return $this->sendSuccessResponse("success", $this->serializerService->entityToArray($user));
    }

    /**
     * Gets a single api token based on the api token id in the request url
     *
     */
    public function getApiToken(PersonalAccessToken $apiToken)
    {
        return $this->sendSuccessResponse("success", $this->serializerService->entityToArray($apiToken));
    }

    /**
     * Gets a list of usee api tokens based on the user id in the request url
     *
     */
    public function getUserApiTokens(User $user, Request $request)
    {
        $getApiTokens = $this->userService->findApiTokensByParams(
            $user,
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($getApiTokens)
        );
    }

    /**
     * Generates a new api token for a single user
     * User is based on the id in the request url
     *
     */
    public function generateNewApiToken(User $user)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray(
                $this->userService->createUserToken($user)
            )
        );
    }

    /**
     * Updates a single token expiry date based on the request post data
     *
     */
    public function updateApiTokenExpiry(User $user, PersonalAccessToken $apiToken, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        return $this->sendSuccessResponse("Successfully updated token.",
            $this->serializerService->entityToArray($this->userService->updateApiTokenExpiry($apiToken, $requestData)));
    }

    /**
     * Delete a single api token based the request post data.
     *
     */
    public function deleteApiToken(User $user, PersonalAccessToken $apiToken, Request $request)
    {
        $delete = $this->userService->deleteApiToken($apiToken);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting api token", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->sendSuccessResponse("Api Token deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }

    /**
     * Creates a user based on the request post data
     *
     */
    public function createUser(Request $request) {
        $create = $this->userService->createUser(
            $this->httpRequestService->getRequestData($request, true));

        if(!$create) {
            return $this->sendErrorResponse("Error inserting user");
        }
        return $this->sendSuccessResponse("User inserted",
            $this->serializerService->entityToArray($create, ['main']));
    }

    /**
     * Updates a user based on the post request data
     *
     */
    public function updateUser(User $user, Request $request)
    {
        $update = $this->userService->updateUser(
            $user,
            $this->httpRequestService->getRequestData($request, true));
        if(!$update) {
            return $this->sendErrorResponse("Error updating user");
        }
        return $this->sendSuccessResponse("User updated",
            $this->serializerService->entityToArray($update, ['main']));
    }

    /**
     * Deletes a user based on the post request data
     *
     */
    public function deleteUser(User $user, Request $request)
    {
        $delete = $this->userService->deleteUser($user);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting user", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->sendSuccessResponse("User deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
