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

    public function findByParams(string $sort,  string $order, ?int $count = null)
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

    public function createUser(array $userData, array $roles) {
        $user = $this->getModel()->fill($userData);
        $createUser = $user->save();
        if (!$createUser) {
            return false;
        }
        $this->getModel()->roles()->sync($roles);
        return true;
    }
    public function updateUser(User $user, array $userData, ?array $roles = []) {
        $this->setModel($user);
        $createUser = $this->save($userData);
        if (!$createUser) {
            return false;
        }
        if (count($roles) > 0) {
            $user->roles()->sync($roles);
        }
        return true;
    }

}
