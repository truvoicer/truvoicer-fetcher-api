<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\User\UserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private UserAdminService $userAdminService;
    public function __construct(UserAdminService $userAdminService)
    {
        $this->userAdminService = $userAdminService;
    }

    public function login(LoginRequest $request) {
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
            return response()->json([
                'message' => 'Error generating token'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return response()->json([
            'token' => $token
        ]);
    }

    public function validateToken(Request $request) {
        return $this->sendSuccessResponse(
            'Authenticated'
        );
    }
}
