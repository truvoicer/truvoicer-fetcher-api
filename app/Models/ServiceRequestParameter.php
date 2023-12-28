<?php

namespace App\Models;

use App\Repositories\ServiceRequestParameterRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestParameter extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_request_parameters';
    public const REPOSITORY = ServiceRequestParameterRepository::class;
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
