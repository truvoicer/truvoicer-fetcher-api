<?php

namespace App\Traits\User;

use App\Models\User;
use App\Repositories\UserRepository;

trait UserTrait
{

    protected User $user;
    protected UserRepository $userRepository;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

}
