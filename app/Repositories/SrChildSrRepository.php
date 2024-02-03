<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrChildSr;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use App\Models\SResponseKey;

class SrChildSrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrChildSr::class);
    }

    public function getModel(): SrChildSr
    {
        return parent::getModel();
    }

    public function updateChildSr(Sr $parentSr,  Sr $childSr)
    {
        if (!$parentSr->exists) {
            return false;
        }
        if (!$childSr->exists) {
            return false;
        }
        $parentSr->childSrs()->attach($childSr->id);
        return true;
    }
    public function saveParentChildSr(Sr $parentSr,  Sr $childSr)
    {
        if (!$parentSr->exists) {
            return false;
        }
        if (!$childSr->exists) {
            return false;
        }
        $parentSr->childSrs()->attach($childSr->id);
        return true;
    }
}
