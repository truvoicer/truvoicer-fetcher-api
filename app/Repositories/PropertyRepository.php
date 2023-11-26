<?php

namespace App\Repositories;

use App\Models\Property;

class PropertyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Property::class);
    }
}
