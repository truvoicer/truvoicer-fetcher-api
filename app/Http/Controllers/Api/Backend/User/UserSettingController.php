<?php

namespace App\Http\Controllers\Api\Backend\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Setting\UpdateUserSettingRequest;
use App\Http\Resources\User\Setting\UserSettingResource;
use App\Services\User\UserSettingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains api endpoint functions for admin related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class UserSettingController extends Controller
{

    public function __construct(
        private UserSettingService $userSettingService,
    ) {
        parent::__construct();
    }

    public function show(Request $request)
    {
        $this->userSettingService->setUser($request->user());
        return new UserSettingResource(
            $this->userSettingService->findUserSettings()
        );
    }

    public function update(UpdateUserSettingRequest $request)
    {
        $this->userSettingService->setUser($request->user());
        if (
            !$this->userSettingService->updateUserSettings(
                $request->validated()
            )->exists
        ) {
            return response()->json([
                'message' => 'Error updating user settings'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Successfully updated user settings'
        ]);
    }
}
