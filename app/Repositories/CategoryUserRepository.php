<?php

namespace App\Repositories;

use App\Models\CategoryUser;

class CategoryUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(CategoryUser::class);
    }

}
