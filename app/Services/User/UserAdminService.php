<?php

namespace App\Services\User;

use App\Helpers\Db\DbHelpers;
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
    public function __construct(
        private AuthService $authService
    )
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
        $this->userRepository->setPagination(true);
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
        $roleIds = $this->authService->getRoleIds($roles);
        return $this->userRepository->createUser($userData, $roleIds);
    }

    public function getUserToken(User $user) {
        $token = $this->personalAccessTokenRepository->getLatestAccessToken($user);
        if ($token instanceof PersonalAccessToken) {
            return $token;
        }
        return $this->createUserToken($user);
    }

    public function getUserRoles(User $user) {
        $appUserRoleData = AuthService::getApiAbilityData(AuthService::ABILITY_APP_USER);
        return $this->roleRepository->fetchUserRoles($user, [$appUserRoleData['name']]);
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
        $availableRoles = AuthService::DEFAULT_ROLES;
        $roles = $user->roles()
            ->whereIn('name', array_column($availableRoles, 'name'))
            ->get();
        $roles = $roles->sort(function ($a, $b) use ($availableRoles) {
            $aRole = array_search($a->name, array_column($availableRoles, 'name'));
            $bRole = array_search($b->name, array_column($availableRoles, 'name'));
            if ($aRole === $bRole) {
                return 0;
            }
            return ($aRole < $bRole) ? -1 : 1;
        });

        $role = $roles->first();
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

    public function updateApiTokenExpiry(PersonalAccessToken $apiToken, array $data)
    {
        return $this->personalAccessTokenRepository->updateTokenExpiry(
            $apiToken,
            $data
        );
    }

    public function findApiTokensByParams(User $user, string $sort, string $order, ?int $count = null)
    {
        return $user->tokens()->orderBy($sort, $order)->limit($count)->paginate();
    }

    public function updateUser(User $user, array $data, ?array $roles = [])
    {
        $roleIds = $this->authService->getRoleIds($roles);
        return $this->userRepository->updateUser($user, $data, $roleIds);
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

    public function deleteBatchUser(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No user ids provided.");
        }
        return $this->userRepository->deleteBatch($ids);
    }
    public function getPersonalAccessTokenRepository(): PersonalAccessTokenRepository
    {
        return $this->personalAccessTokenRepository;
    }

}
