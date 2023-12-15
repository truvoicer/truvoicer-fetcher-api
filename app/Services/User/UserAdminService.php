<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
class UserAdminService extends BaseService
{
    private UserRepository $userRepository;
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function createUser(array $userData)
    {
        $userData['password'] = Hash::make($userData['password']);
        $user = User::create($userData);
        $this->setUser($user);
        return $user->save();
    }

    public function createPublicUserToken()
    {
        $getAbility = AuthService::getApiAbility(AuthService::ABILITY_APP_USER);
        if (!$getAbility) {
            return false;
        }
        $token = $this->getUser()->createToken('admin', [$getAbility])->plainTextToken;
        return $token;
    }
    public function createUserToken(int $roleId)
    {
        $role = Role::where('id', $roleId)->first();
        if (!$role instanceof Role) {
            return false;
        }
        $getAbility = AuthService::getApiAbility($role->name);
        if (!$getAbility) {
            return false;
        }
        $token = $this->getUser()->createToken('admin', [$getAbility])->plainTextToken;
        return $token;
    }

    public function getUserByEmail(string $email)
    {
        return $this->userRepository->getUserByEmail($email);
    }

}
