<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\User\UserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private UserAdminService $userAdminService;
    public function __construct(
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        UserAdminService $userAdminService,
        AccessControlService $accessControlService,
        Request $request
    )
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
        $this->userAdminService = $userAdminService;
    }

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request->get('email'))->first();
        if (!$user) {
            return response()->json([
                'message' => 'Invalid user'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!Hash::check($request->get('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('admin', ['api:superuser'])->plainTextToken;
        if (!$token) {
            return $this->sendErrorResponse(
                'Error generating token',
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        return $this->sendSuccessResponse(
            'Authenticated',
            [
                'token' => $token
            ]
        );
    }

    public function validateToken(): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse(
            'Authenticated'
        );
    }

    public function getSingleUserByApiToken(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse("success", $request->user());
    }

    public function accountTokenLogin(Request $request): Response
    {
        $user = $request->user();
        $apiToken = $user->currentAccessToken();
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => 'Successfully logged in.',
            'session' => [
                "email" => $user->email,
                "access_token" => $apiToken,
//                "expires_at" => $apiToken->getExpiresAt()->getTimestamp()
            ],
        ];
        return $this->sendSuccessResponse("success", $data);
    }

    public function getAccountDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->userAdminService->getUserByEmail($request->get("email"));
        return $this->sendSuccessResponse("Success", $user);
    }

    public function newToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->userAdminService->getUserByEmail($request->get("email"));
        $token = $user->createToken('admin', ['api:superuser'])->plainTextToken;
        if (!$token) {
            return $this->sendErrorResponse(
                'Error generating token',
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        return $this->sendSuccessResponse("Api token", [
            "token: " => $token,
//            "expiresAt" => $setApiToken->getExpiresAt()->format("Y-m-d H:i:s"),
            "email" => $user->email
        ]);
    }
}
