<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\PersonalAccessTokenRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserAdminService extends BaseService
{
    const DEFAULT_TOKEN_EXPIRY = '+1 days';
    const NO_TOKEN_EXPIRY = 'NO_TOKEN_EXPIRY';
    private PersonalAccessTokenRepository $personalAccessTokenRepository;
    private RoleRepository $roleRepository;
    public function __construct()
    {
        $this->setUserRepository(new UserRepository());
        $this->personalAccessTokenRepository = new PersonalAccessTokenRepository();
        $this->roleRepository = new RoleRepository();
    }

    public static function userTokenHasAbility(User $user, string $ability) {
        return $user->tokenCan(AuthService::getApiAbility($ability));
    }

    public function findByParams(string $sort, string $order, int $count) {
        return $this->userRepository->findAllWithParams($sort, $order, $count);
    }

    public function createUserByRoleId(array $userData, int $roleId)
    {
        $role = $this->roleRepository->findById($roleId);
        if (!$role instanceof Role) {
            return false;
        }
        return $this->createUser($userData, $role);
    }
    public function createUser(array $userData, Role $role)
    {
        $user = $this->userRepository->getModel()->fill($userData);
        $createUser = $role->users()->save($user);
        return $createUser->exists;
    }

    public function getUserToken(User $user) {
        $token = $this->personalAccessTokenRepository->getLatestAccessToken($user);
        if ($token instanceof PersonalAccessToken) {
            return $token;
        }
        return $this->createUserToken($user);
    }

    public function createUserToken(User $user, ?string $expiry = self::DEFAULT_TOKEN_EXPIRY)
    {
        $role = $user->roles()->first();
        if (!$role instanceof Role) {
            return false;
        }
        switch ($expiry) {
            case self::NO_TOKEN_EXPIRY:
                $expiry = null;
                break;
        }

        $user->tokens()->delete();
        return $user->createToken($role->name, [$role->ability], new \DateTime($expiry));
    }

    public function getUserByEmail(string $email)
    {
        return $this->userRepository->getUserByEmail($email);
    }

    public function apiTokenBelongsToUser(User $user, PersonalAccessToken $apiToken)
    {
        return $apiToken->user()->id === $user->id;
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
