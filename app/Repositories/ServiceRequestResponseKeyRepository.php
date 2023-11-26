<?php

namespace App\Repositories;

use App\Models\ServiceRequestResponseKey;

class ServiceRequestResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestResponseKey::class);
    }
}
