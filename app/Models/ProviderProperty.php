<?php

namespace App\Models;

use App\Repositories\ProviderPropertyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderProperty extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'provider_properties';
    public const REPOSITORY = ProviderPropertyRepository::class;
}
