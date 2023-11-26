<?php

namespace App\Repositories;

use App\Models\ServiceRequestParameter;

class ServiceRequestParameterRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestParameter::class);
    }
}
