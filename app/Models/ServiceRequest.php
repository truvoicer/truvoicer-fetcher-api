<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function serviceRequestConfig()
    {
        return $this->hasMany(ServiceRequestConfig::class);
    }
    public function serviceRequestParameter()
    {
        return $this->hasMany(ServiceRequestParameter::class);
    }
    public function serviceRequestResponseKey()
    {
        return $this->hasMany(ServiceRequestResponseKey::class);
    }
}
