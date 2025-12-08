<?php

namespace App\Repositories\MongoDB;

use App\Models\S;
use App\Models\Sr;
use Illuminate\Support\Facades\DB;
use function Termwind\parse;

class MongoDBRepository extends BaseRepository
{
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getCollectionName(Sr $sr)
    {
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            return false;
        }
        return $this->getCollectionNameByService($service, $sr->type->value);
    }
    public function getCollectionNameByService(S $service, string $type)
    {
        return sprintf(
            '%s_%s',
            $service->name,
            $type
        );
    }
}
