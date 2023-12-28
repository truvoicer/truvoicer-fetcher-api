<?php

namespace App\Models;

use App\Repositories\ServiceResponseKeyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceResponseKey extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_response_keys';
    public const REPOSITORY = ServiceResponseKeyRepository::class;
    public function serviceRequestResponseKey()
    {
        return $this->belongsTo(ServiceRequestResponseKey::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
