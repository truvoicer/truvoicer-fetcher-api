<?php

namespace App\Repositories;

use App\Models\Sr;
use App\Models\SrChildSr;

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
    public function saveParentChildSrById(int $parentSrId,  Sr $childSr) {
        $srRepository = new SrRepository();
        $parentSr = $srRepository->findById($parentSrId);
        if (!$parentSr instanceof Sr) {
            return false;
        }
        return $this->saveParentChildSr($parentSr, $childSr);
    }
    public function saveParentChildSr(Sr $parentSr,  Sr $childSr)
    {
        if (!$parentSr->exists) {
            return false;
        }
        if (!$childSr->exists) {
            return false;
        }
        $childSr->parentSrs()->get()->each(function ($parentSr) use ($childSr) {
            $parentSr->childSrs()->detach($childSr->id);
        });
        $parentSr->childSrs()->attach($childSr->id);
        return true;
    }

    public function saveChildSrOverrides(Sr $serviceRequest, array $data) {
        $this->setModel($serviceRequest);
//        $parentSr = $serviceRequest->parentSrs()->first();
//
//        if (!$parentSr instanceof Sr) {
//            return false;
//        }
        return $this->getModel()->pivot->update($data);
    }
}
