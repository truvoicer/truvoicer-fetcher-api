<?php

namespace App\Controller\Api;

use App\Service\Permission\AccessControlService;
use App\Service\SecurityService;
use App\Service\Tools\HttpRequestService;
use App\Service\Tools\SerializerService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for user account tasks via email password login
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class AuthController extends BaseController
{
    private UserService $userService;

    /**
     * AuthController constructor.
     * Initialise services for this class
     *
     * @param UserService $userService
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(UserService $userService,
                                SerializerService $serializerService,
                                HttpRequestService $httpRequestService,
                                AccessControlService $accessControlService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->userService = $userService;
    }

    /**
     * Gets a single user based on the id in the request url
     *
     * @Route("/api/auth/token/user", name="api_get_user_by_token", methods={"POST"})
     * @param Request $request
     * @param SecurityService $securityService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSingleUserByApiToken(Request $request, SecurityService $securityService)
    {
        $apiTokenValue = $securityService->getTokenFromHeader($request->headers->get('Authorization'));
        $apiToken = $this->userService->getTokenByValue($apiTokenValue);
        if ($apiToken === null) {
            return $this->jsonResponseFail("Api Token not found.", []);
        }
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityToArray($apiToken->getUser()));
    }

    /**
     * API user login
     * Returns user api token data
     *
     * @Route("/api/account/login", name="api_account_login", methods={ "POST" })
     * @param Request $request
     * @return Response
     */
    public function accountLogin(Request $request): Response
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $user = $this->userService->getUserByEmail($requestData["email"]);
        if ($user === null) {
            return $this->jsonResponseFail("User not found.", []);
        }
        $apiToken = $this->userService->getLatestToken($user);
        if ($apiToken === null) {
            $apiToken = $this->userService->setApiToken($user, "auto");
        }
        $this->userService->deleteUserExpiredTokens($user);
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => 'Successfully logged in.',
            'session' => [
                "access_token" => $apiToken->getToken(),
                "expires_at" => $apiToken->getExpiresAt()->getTimestamp()
            ],
        ];

        return $this->jsonResponseSuccess("success", $data);
    }
    /**
     * API user login
     * Returns user api token data
     *
     * @Route("/api/token/login", name="api_token_login", methods={ "POST", "GET" })
     * @param Request $request
     * @return Response
     */
    public function accountTokenLogin(): Response
    {
        $user = $this->getUser();
        $apiToken = $this->userService->getLatestToken($user);
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => 'Successfully logged in.',
            'session' => [
                "email" => $user->getEmail(),
                "access_token" => $apiToken->getToken(),
                "expires_at" => $apiToken->getExpiresAt()->getTimestamp()
            ],
        ];
        return $this->jsonResponseSuccess("success", $data);
    }

    /**
     * Gets user data
     *
     * @Route("/api/account/details", name="api_get_account_details", methods={ "POST" })
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAccountDetails(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $user = $this->userService->getUserByEmail($requestData["email"]);
        return $this->jsonResponseSuccess("Success",
            $this->serializerService->entityToArray($user));
    }

    /**
     * Generates a new token for a user
     *
     * @Route("/api/account/new-token", name="new_token", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newToken(Request $request) {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $user = $this->userService->getUserByEmail($requestData["email"]);
        $setApiToken = $this->userService->setApiToken($user, "auto");
        if(!$setApiToken) {
            return $this->jsonResponseFail("Error generating api token");
        }
        return $this->jsonResponseSuccess("Api token", [
            "token: " => $setApiToken->getToken(),
            "expiresAt" => $setApiToken->getExpiresAt()->format("Y-m-d H:i:s"),
            "email" => $setApiToken->getuser()->getEmail()
        ]);
    }
}
