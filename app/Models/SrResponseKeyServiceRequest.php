<?php

namespace App\Models;

use App\Repositories\SrResponseKeyServiceRequestRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrResponseKeyServiceRequest extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'sr_response_key_service_requests';
    public const REPOSITORY = SrResponseKeyServiceRequestRepository::class;
}
