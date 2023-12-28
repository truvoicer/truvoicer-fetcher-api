<?php

namespace App\Models;

use App\Repositories\PropertyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'properties';
    public const REPOSITORY = PropertyRepository::class;
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
