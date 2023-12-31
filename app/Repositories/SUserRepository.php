<?php

namespace App\Repositories;

use App\Models\SUser;

class SUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SUser::class);
    }

    public function getModel(): SUser
    {
        return parent::getModel();
    }
}
