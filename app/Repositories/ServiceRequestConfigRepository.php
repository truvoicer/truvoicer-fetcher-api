<?php

namespace App\Repositories;

use App\Models\ServiceRequestConfig;

class ServiceRequestConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestConfig::class);
    }
}
