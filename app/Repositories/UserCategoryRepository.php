<?php

namespace App\Repositories;

use App\Models\UserCategory;

class UserCategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserCategory::class);
    }
}
