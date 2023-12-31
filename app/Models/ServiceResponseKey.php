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
    protected $fillable = [
        'name',
    ];
    public function serviceRequestResponseKey()
    {
        return $this->hasMany(ServiceRequestResponseKey::class);
    }
    public function serviceRequestResponseKeys()
    {
        return $this->belongsToMany(
            ServiceResponseKey::class,
            ServiceRequestResponseKey::TABLE_NAME,
            'service_request__id',
            'service_response_key_id'
        );
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
