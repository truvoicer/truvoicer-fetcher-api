<?php

namespace App\Repositories;

use App\Models\ProviderCategory;

class ProviderCategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderCategory::class);
    }

    public function getModel(): ProviderCategory
    {
        return parent::getModel();
    }

}
