<?php

namespace App\Repositories;

use App\Models\ProviderProperty;

class ProviderPropertyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderProperty::class);
    }
}
