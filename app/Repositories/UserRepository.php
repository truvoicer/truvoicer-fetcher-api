<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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


}
