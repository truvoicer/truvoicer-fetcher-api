<?php

namespace App\Models;

use App\Repositories\ProviderUserRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceUserRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'services';
    public const REPOSITORY = ServiceRepository::class;
    public const RELATED_USER_REPOSITORY = ServiceUserRepository::class;
    protected $fillable = [
        'name',
        'label'
    ];
    public function serviceRequest()
    {
        return $this->hasMany(ServiceRequest::class);
    }
    public function serviceResponseKey()
    {
        return $this->hasMany(ServiceResponseKey::class);
    }
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            ServiceUser::TABLE_NAME,
            'service_id',
            'user_id'
        );
    }
    public function permissions() {
        return $this->hasManyThrough(ServiceUserPermission::class, ServiceUser::class);
    }
    public function serviceUser()
    {
        return $this->hasMany(
            ServiceUser::class
        );
    }
}
