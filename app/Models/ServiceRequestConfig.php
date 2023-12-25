<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestConfig extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_request_configs';
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
