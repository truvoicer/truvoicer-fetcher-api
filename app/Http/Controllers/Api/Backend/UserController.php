<?php
namespace App\Controller\Api\Backend;

use App\Controller\Api\BaseController;
use App\Entity\ApiToken;
use App\Entity\User;
use App\Service\ApiServices\ServiceRequests\RequestService;
use App\Service\Category\CategoryService;
use App\Service\Permission\AccessControlService;
use App\Service\Permission\PermissionEntities;
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
 * @IsGranted("ROLE_USER")
 */
class UserController extends BaseController
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
     * Gets a single user based on the id in the request url
     *
     * @Route("/api/user/detail", name="api_get_user_detail", methods={"GET"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSessionUserDetail()
    {
        return $this->jsonResponseSuccess(
            "success",
            $this->serializerService->entityToArray($this->getUser())
        );
    }

    /**
     * Gets a user mappings
     *
     * @Route("/api/user/permission/entity/list", name="api_get_session_user_entity_list", methods={"GET"})
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProtectedEntitiesList()
    {
        return $this->jsonResponseSuccess(
            "success",
            PermissionEntities::PROTECTED_ENTITIES
        );
    }

    /**
     * Gets a user mappings
     *
     * @Route("/api/user/permission/entity/{entity}/list", name="api_get_single_session_entity_permission_list", methods={"GET"})
     * @param string $entity
     * @param User $user
     * @param ProviderService $providerService
     * @param CategoryService $categoryService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserEntityPermissionList(string $entity)
    {
        return $this->jsonResponseSuccess(
            "Successfully fetched permission list",
            $this->serializerService->entityArrayToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList($entity, $this->getUser()),
                ["list"]
            )
        );
    }

    /**
     * Gets a user mappings
     *
     * @Route("/api/user/permission/entity/{entity}/{id}", name="api_get_single_session_user_entity_permission", methods={"GET"})
     * @param string $entity
     * @param User $user
     * @param ProviderService $providerService
     * @param CategoryService $categoryService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserEntityPermission(string $entity, int $id)
    {
        return $this->jsonResponseSuccess(
            "Successfully fetched permissions",
            $this->serializerService->entityToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermission($entity, $id, $this->getUser()),
                ["list"]
            )
        );
    }

    /**
     * Gets a single api token based on the api token id in the request url
     *
     * @Route("/api/user/api-token/{id}/detail", name="api_get_session_user_api_token", methods={"GET"})
     * @param ApiToken $apiToken
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSessionUserApiToken(ApiToken $apiToken)
    {
        if (!$this->userService->apiTokenBelongsToUser($this->getUser(), $apiToken)) {
            return $this->jsonResponseFail(
                "Operation not permitted"
            );
        }
        return $this->jsonResponseSuccess(
            "success",
            $this->serializerService->entityToArray($apiToken)
        );
    }

    /**
     * Gets a list of usee api tokens based on the user id in the request url
     *
     * @Route("/api/user/api-tokens", name="api_get_session_user_api_token_list", methods={"GET"})
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSessionUserApiTokenList(Request $request)
    {
        $getApiTokens = $this->userService->findApiTokensByParams(
            $this->getUser(),
            $request->get('sort', "id"),
            $request->get('order', "asc"),
            (int) $request->get('count', null)
        );
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray(
                array_filter($getApiTokens, function ($token, $key) {
                    return $token->getType() === "user";
                }, ARRAY_FILTER_USE_BOTH)
            )
        );
    }

    /**
     * Generates a new api token for a single user
     * User is based on the id in the request url
     *
     * @Route("/api/user/api-token/generate", name="generate_session_user_api_token", methods={"GET"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function generateSessionUserApiToken()
    {
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityToArray(
                $this->userService->setApiToken($this->getUser(), "user")
            )
        );
    }

    /**
     * Delete a single api token based the request post data.
     *
     * @Route("/api/user/api-token/delete", name="session_user_api_token_delete", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteSessionUserApiToken(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $apiToken = $this->userService->getApiTokenById($requestData['item_id']);

        if (!$this->userService->apiTokenBelongsToUser($this->getUser(), $apiToken)) {
            return $this->jsonResponseFail(
                "Operation not permitted"
            );
        }
        $delete = $this->userService->deleteApiToken($apiToken);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting api token", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->jsonResponseSuccess("Api Token deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }

    /**
     * Updates a user based on the post request data
     *
     * @param Request $request
     * @Route("/api/user/update", name="api_update_session_user", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateSessionUser(Request $request)
    {
        $update = $this->userService->updateSessionUser(
            $this->getUser(),
            $this->httpRequestService->getRequestData($request, true));
        if(!$update) {
            return $this->jsonResponseFail("Error updating user");
        }
        return $this->jsonResponseSuccess("User updated",
            $this->serializerService->entityToArray($update, ['main']));
    }
}
