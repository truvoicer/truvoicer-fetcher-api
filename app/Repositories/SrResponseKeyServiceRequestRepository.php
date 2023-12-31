<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;
use App\Models\SrResponseKeyServiceRequest;
use App\Models\ServiceResponseKey;

class SrResponseKeyServiceRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrResponseKeyServiceRequest::class);
    }

    public function getModel(): SrResponseKeyServiceRequest
    {
        return parent::getModel();
    }

}
