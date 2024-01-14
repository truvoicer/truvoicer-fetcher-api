<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\PersonalAccessTokenRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
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
        parent::__construct();
        $this->setUserRepository(new UserRepository());
        $this->personalAccessTokenRepository = new PersonalAccessTokenRepository();
        $this->roleRepository = new RoleRepository();
    }

    public static function userTokenHasAbility(User $user, string $ability) {
        return $user->tokenCan(AuthService::getApiAbility($ability));
    }

    public function findByParams(string $sort, string $order, ?int $count = null) {
        return $this->userRepository->findAllWithParams($sort, $order, $count);
    }
    public function findUserRoles(string $sort, string $order, ?int $count = null) {
        return $this->roleRepository->findAllWithParams($sort, $order, $count);
    }

    public function createUserByRoleId(array $userData, array $roles)
    {
        return $this->createUser($userData, $roles);
    }
    public function createUser(array $userData, array $roles)
    {
        return $this->userRepository->createUser($userData, $roles);
    }

    public function getUserToken(User $user) {
        $token = $this->personalAccessTokenRepository->getLatestAccessToken($user);
        if ($token instanceof PersonalAccessToken) {
            return $token;
        }
        return $this->createUserToken($user);
    }

    /**
     * @throws \Exception
     */
    public function createUserTokenByRoleId(User $user, int $roleId, ?string $expiry = null)
    {
        $role = $this->roleRepository::findUserRoleBy($user, ['role_id' => $roleId]);
        if (!$role instanceof Role) {
            return false;
        }
        if (empty($expiry)) {
            $expiry = self::DEFAULT_TOKEN_EXPIRY;
        }
        return $this->createUserTokenByRole($user, $role, new \DateTime($expiry));
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

        return $this->createUserTokenByRole($user, $role, new \DateTime($expiry));
    }
    public function createUserTokenByRole(User $user, Role $role, \DateTime $expiry)
    {
        return $user->createToken($role->name, [$role->ability], $expiry);
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
        return $this->personalAccessTokenRepository->updateTokenExpiry(
            $apiToken,
            $data
        );
    }

    public function findApiTokensByParams(User $user, string $sort, string $order, ?int $count = null)
    {
        return $user->tokens()->orderBy($sort, $order)->limit($count)->get();
    }

    public function updateUser(User $user, array $data, ?array $roles = [])
    {
        return $this->userRepository->updateUser($user, $data, $roles);
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

    public function getPersonalAccessTokenRepository(): PersonalAccessTokenRepository
    {
        return $this->personalAccessTokenRepository;
    }

}
