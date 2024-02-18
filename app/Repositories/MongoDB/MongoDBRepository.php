<?php

namespace App\Repositories\MongoDB;

use App\Models\S;
use App\Models\Sr;

class MongoDBRepository extends BaseRepository
{

    public function getCollectionName(Sr $sr)
    {
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            return false;
        }
        return sprintf(
            '%s_%s',
            $service->name,
            $sr->name
        );
    }
}
