<?php

namespace App\Repositories;

use App\Models\UserProvider;

class UserProviderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserProvider::class);
    }
}
