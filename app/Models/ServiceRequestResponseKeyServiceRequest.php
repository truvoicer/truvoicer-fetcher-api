<?php

namespace App\Models;

use App\Repositories\ServiceRequestResponseKeyServiceRequestRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResponseKeyServiceRequest extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'service_request_response_key_service_requests';
    public const REPOSITORY = ServiceRequestResponseKeyServiceRequestRepository::class;
}
