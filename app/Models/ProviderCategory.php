<?php

namespace App\Models;

use App\Repositories\ProviderCategoryRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCategory extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'provider_categories';
    public const REPOSITORY = ProviderCategoryRepository::class;
}
