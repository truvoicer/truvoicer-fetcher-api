<?php

namespace App\Repositories;

use App\Models\Service;

class ServiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Service::class);
    }
}
