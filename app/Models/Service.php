<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'services';
    public function serviceRequest()
    {
        return $this->hasMany(ServiceRequest::class);
    }
    public function serviceResponseKey()
    {
        return $this->hasMany(ServiceResponseKey::class);
    }
}
