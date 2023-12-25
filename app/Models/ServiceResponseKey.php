<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceResponseKey extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_response_keys';
    public function serviceRequestResponseKey()
    {
        return $this->belongsTo(ServiceRequestResponseKey::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
