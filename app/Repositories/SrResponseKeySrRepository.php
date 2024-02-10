<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use App\Models\SResponseKey;

class SrResponseKeySrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrResponseKeySr::class);
    }

    public function getModel(): SrResponseKeySr
    {
        return parent::getModel();
    }

    public function saveResponseKeySr(SrResponseKey $srResponseKey,  array $srIds)
    {
        if (!$srResponseKey->exists) {
            return false;
        }
        $srResponseKey->srResponseKeySrs()->whereNotIn('sr_id', $srIds)->detach();
        $srResponseKey->srResponseKeySrs()->sync($srIds);
        return true;
    }
}
