<?php

namespace App\Repositories;

use App\Models\ServiceRequest;

class ServiceRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequest::class);
    }
}
