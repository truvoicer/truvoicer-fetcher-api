<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;
use App\Models\ServiceRequestResponseKeyServiceRequest;
use App\Models\ServiceResponseKey;

class ServiceRequestResponseKeyServiceRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestResponseKeyServiceRequest::class);
    }

    public function getModel(): ServiceRequestResponseKeyServiceRequest
    {
        return parent::getModel();
    }

}
