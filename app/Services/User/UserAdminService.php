<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserAdminService extends BaseService
{
    private UserRepository $userRepository;
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public static function userTokenHasAbility(User $user, string $ability) {
        return $user->tokenCan(AuthService::getApiAbility($ability));
    }

    public function findByParams(string $sort, string $order, int $count) {
        return $this->userRepository->findAllWithParams($sort, $order, $count);
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
        $role = Role::where('name', AuthService::ABILITY_APP_USER)->first();
        if (!$role instanceof Role) {
            return false;
        }
        $token = $this->getUser()->createToken($role->name, [$role->ability])->plainTextToken;
        return $token;
    }
    public function createUserToken(User $user, int $roleId)
    {
        $role = Role::where('id', $roleId)->first();
        if (!$role instanceof Role) {
            return false;
        }
        $token = $user->createToken($role->name, [$role->ability])->plainTextToken;
        return $token;
    }

    public function getUserByEmail(string $email)
    {
        return $this->userRepository->getUserByEmail($email);
    }

    public function apiTokenBelongsToUser(User $user, PersonalAccessToken $apiToken)
    {
        return $apiToken->user()->getId() === $user->getId();
    }

    public function getApiTokenById(int $id)
    {
        return PersonalAccessToken::where('id', $id)->first();
    }

    public function updateApiTokenExpiry(PersonalAccessToken $apiToken, array $data)
    {
        return true;
//        return $this->apiTokenRepository->updateTokenExpiry($apiToken, new \DateTime($data["expires_at"]), "user");
    }

    public function findApiTokensByParams(User $user, string $sort, string $order, int $count)
    {
        return $user->tokens()->orderBy($sort, $order)->limit($count)->get();
    }

    public function generateUserPassword(array $data, $type)
    {
        if ((array_key_exists("change_password", $data) && $data["change_password"]) || $type === "insert") {
            if (!array_key_exists("confirm_password", $data) || !array_key_exists("new_password", $data)) {
                throw new BadRequestHttpException("confirm_password or new_password is not in request.");
            }
            if ($data["confirm_password"] === "" || $data["confirm_password"] === null ||
                $data["new_password"] === "" || $data["new_password"] === null) {
                throw new BadRequestHttpException("Confirm or New Password fields have empty values.");
            }
            if ($data["confirm_password"] !== $data["new_password"]) {
                throw new BadRequestHttpException("Confirm and New Password fields don't match.");
            }
            return Hash::make($data['new_password']);
        }
        return false;
    }

    public function updateUser(User $user, array $data)
    {
        $this->userRepository->setModel($user);
        return $this->userRepository->save($data);
    }

    public function deleteUserById(int $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            throw new BadRequestHttpException(sprintf("User id: %s not found in database.", $userId));
        }
        return $this->deleteUser($user);
    }

    public function deleteUser(User $user)
    {
        $this->userRepository->setModel($user);
        return $this->userRepository->delete();
    }

    public function deleteUserExpiredTokens(User $user)
    {
        return $user->tokens()->where('expires_at', '<', now())->delete();
    }

    public function deleteApiTokenById(int $id)
    {
        $apiToken = PersonalAccessToken::where('id', $id)->first();
        if (!$apiToken instanceof PersonalAccessToken) {
            throw new BadRequestHttpException("ApiToken does not exist in database...");
        }
        return $apiToken->delete();
    }

    public function deleteApiToken(PersonalAccessToken $apiToken)
    {
        return $apiToken->delete();
    }
}
