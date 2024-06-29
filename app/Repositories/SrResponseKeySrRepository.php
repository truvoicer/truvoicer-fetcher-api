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

    public function syncResponseKeySrs(SrResponseKey $srResponseKey, array $syncData)
    {
        if (!$srResponseKey->exists) {
            return false;
        }
        $srIds = [];
        foreach ($syncData as $index => $sr) {
            if (is_array($sr)) {
                $srIds[] = $index;
            } else {
                $srIds[] = $sr;
            }
        }
        SrResponseKeySr::where('sr_response_key_id', $srResponseKey->id)
            ->whereNotIn('sr_id', $srIds)
            ->delete();

        foreach ($syncData as $index => $sr) {
            $srId = is_array($sr) ? $index : $sr;
            $saveData = [
                'sr_id' => $srId,
                'sr_response_key_id' => $srResponseKey->id,
            ];
            if (is_array($sr)) {
                $saveData['response_keys'] = $sr;
            }
            SrResponseKeySr::updateOrCreate(
                [
                    'sr_id' => $srId,
                    'sr_response_key_id' => $srResponseKey->id
                ],
                $saveData
            );
        }
        return true;
    }
}
