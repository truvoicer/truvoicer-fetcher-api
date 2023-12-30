<?php

namespace App\Models;

use App\Repositories\ServiceRequestConfigRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestConfig extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'service_request_configs';
    public const REPOSITORY = ServiceRequestConfigRepository::class;

    protected $casts = [
        'value_choices' => 'array',
        'array_value' => 'array',
    ];
    protected $fillable = [
        'name',
        'value',
        'value_type',
        'array_value',
    ];
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
