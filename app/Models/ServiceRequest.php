<?php

namespace App\Models;

use App\Repositories\ServiceRequestRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'service_requests';
    public const REPOSITORY = ServiceRequestRepository::class;
    protected $fillable = [
        'name',
        'label',
        'pagination_type',
    ];
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
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
