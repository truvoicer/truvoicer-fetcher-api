<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AccessTokenResource;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Services\User\UserSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;

class AuthController extends Controller
{
    public function __construct(
        private UserAdminService $userAdminService,
        private UserSettingService $userSettingService
    ) {
        parent::__construct();
    }

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request->get('email'))->first();
        if (! $user) {
            return response()->json([
                'message' => 'Invalid user',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (! Hash::check($request->get('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->userAdminService->createUserToken($user);

        if (! $token) {
            return $this->sendErrorResponse(
                'Error generating token',
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->sendSuccessResponse(
            'Authenticated',
            new AccessTokenResource($token)
        );
    }

    public function validateToken(): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse(
            'Authenticated'
        );
    }

    public function getRoleList(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->sendSuccessResponse(
            'Authenticated',
            RoleResource::collection(
                $this->userAdminService->getUserRoles($request->user())
            )
        );
    }

    public function getSingleUserByApiToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->userSettingService->setUser($request->user());
        $userSettings = $this->userSettingService->findUserSettings();
        if (! $userSettings->exists) {
            return $this->sendErrorResponse('Error finding/initialising user settings');
        }

        return $this->sendSuccessResponse(
            'success',
            new UserResource($request->user()->load('settings'))
        );
    }

    public function getAccountDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->userAdminService->getUserByEmail($request->get('email'));

        return $this->sendSuccessResponse('Success', $user);
    }

    public function newToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $generateToken = $this->userAdminService->createUserTokenByRoleId(
            $request->user(),
            $request->get('role_id'),
            $request->get('expires_at', null),
        );

        if (! $generateToken) {
            return $this->sendErrorResponse(
                'Error generating api token',
            );
        }

        return $this->sendSuccessResponse(
            'success',
            new PersonalAccessTokenResource(
                $generateToken
            )
        );
    }
}
