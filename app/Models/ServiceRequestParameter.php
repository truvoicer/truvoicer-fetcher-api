<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestParameter extends Model
{
    use HasFactory;
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
