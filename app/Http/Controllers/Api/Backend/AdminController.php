<?php
namespace App\Controller\Api\Backend;

use App\Controller\Api\BaseController;
use App\Entity\ApiToken;
use App\Entity\User;
use App\Service\ApiServices\ServiceRequests\RequestService;
use App\Service\Category\CategoryService;
use App\Service\Permission\AccessControlService;
use App\Service\Provider\ProviderService;
use App\Service\Tools\HttpRequestService;
use App\Service\SecurityService;
use App\Service\Tools\SerializerService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for admin related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_ADMIN")
 * @Route("/api/admin/user")
 */
class AdminController extends BaseController
{
    private UserService $userService;

    /**
     * AdminController constructor.
     * Initialises services used in this controller
     *
     * @param UserService $userService
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(UserService $userService, SerializerService $serializerService,
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
     * @Route("/list", name="api_get_users", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUsersList(Request $request)
    {
        $getUsers = $this->userService->findByParams(
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );
        return $this->jsonResponseSuccess("success", $this->serializerService->entityArrayToArray($getUsers));
    }

    /**
     * Gets a single user based on the id in the request url
     *
     * @Route("/{id}", name="api_get_single_user", methods={"GET"})
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSingleUser(User $user)
    {
        return $this->jsonResponseSuccess("success", $this->serializerService->entityToArray($user));
    }

    /**
     * Gets a single api token based on the api token id in the request url
     *
     * @Route("/api-token/{apiToken}/detail", name="api_get_single_api_token", methods={"GET"})
     * @param ApiToken $apiToken
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getApiToken(ApiToken $apiToken)
    {
        return $this->jsonResponseSuccess("success", $this->serializerService->entityToArray($apiToken));
    }

    /**
     * Gets a list of usee api tokens based on the user id in the request url
     *
     * @Route("/{user}/api-tokens", name="api_get_user_api_tokens", methods={"GET"})
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserApiTokens(User $user, Request $request)
    {
        $getApiTokens = $this->userService->findApiTokensByParams(
            $user,
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray($getApiTokens)
        );
    }

    /**
     * Generates a new api token for a single user
     * User is based on the id in the request url
     *
     * @Route("/{user}/api-token/generate", name="generate_user_api_token", methods={"GET"})
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function generateNewApiToken(User $user)
    {
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityToArray($this->userService->setApiToken($user, "user")));
    }

    /**
     * Updates a single token expiry date based on the request post data
     *
     * @Route("/{user}/api-token/{apiToken}/update", name="user_api_token_expiry", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateApiTokenExpiry(User $user, ApiToken $apiToken, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        return $this->jsonResponseSuccess("Successfully updated token.",
            $this->serializerService->entityToArray($this->userService->updateApiTokenExpiry($apiToken, $requestData)));
    }

    /**
     * Delete a single api token based the request post data.
     *
     * @Route("/{user}/api-token/{apiToken}/delete", name="user_api_token_delete", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteApiToken(User $user, ApiToken $apiToken, Request $request)
    {
        $delete = $this->userService->deleteApiToken($apiToken);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting api token", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->jsonResponseSuccess("Api Token deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }

    /**
     * Creates a user based on the request post data
     *
     * @param Request $request
     * @Route("/create", name="api_create_user", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createUser(Request $request) {
        $create = $this->userService->createUser(
            $this->httpRequestService->getRequestData($request, true));

        if(!$create) {
            return $this->jsonResponseFail("Error inserting user");
        }
        return $this->jsonResponseSuccess("User inserted",
            $this->serializerService->entityToArray($create, ['main']));
    }

    /**
     * Updates a user based on the post request data
     *
     * @param Request $request
     * @Route("/{user}/update", name="api_update_user", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateUser(User $user, Request $request)
    {
        $update = $this->userService->updateUser(
            $user,
            $this->httpRequestService->getRequestData($request, true));
        if(!$update) {
            return $this->jsonResponseFail("Error updating user");
        }
        return $this->jsonResponseSuccess("User updated",
            $this->serializerService->entityToArray($update, ['main']));
    }

    /**
     * Deletes a user based on the post request data
     *
     * @param Request $request
     * @Route("/{user}/delete", name="api_delete_user", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteUser(User $user, Request $request)
    {
        $delete = $this->userService->deleteUser($user);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting user", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->jsonResponseSuccess("User deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}