<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResponseKey extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_request_response_keys';
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
    public function serviceResponseKey()
    {
        return $this->hasMany(ServiceResponseKey::class);
    }
}
