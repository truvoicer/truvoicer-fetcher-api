<?php

namespace App\Repositories;

use App\Models\ServiceUser;

class ServiceUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceUser::class);
    }

    public function getModel(): ServiceUser
    {
        return parent::getModel();
    }
}
