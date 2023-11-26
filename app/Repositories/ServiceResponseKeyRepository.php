<?php

namespace App\Repositories;

use App\Models\ServiceResponseKey;

class ServiceResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceResponseKey::class);
    }
}
