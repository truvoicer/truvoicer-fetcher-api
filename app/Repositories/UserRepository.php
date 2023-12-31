<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function findByParams(string $sort,  string $order, int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }
    public function getUserByEmail(string $email)
    {
        $this->addWhere('email', $email);
        return $this->findOne();
    }

    public function getModel(): User
    {
        return parent::getModel();
    }

    public function createUser(array $userData, Role $role) {
        $user = $this->getModel()->fill($userData);
        $createUser = $role->users()->save($user);
        if (!$createUser->exists) {
            return false;
        }
        $this->setModel($createUser);
        return true;
    }
    public function updateUser(User $user, array $userData, ?Role $role = null) {
        if ($role instanceof Role) {
            unset($userData['role_id']);
            $user->roles()->sync([$role->id]);
        }
        $this->setModel($user);
        $createUser = $this->save($userData);
        if (!$createUser) {
            return false;
        }
        return true;
    }

}
