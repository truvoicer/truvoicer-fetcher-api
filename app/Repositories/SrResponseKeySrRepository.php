<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use App\Models\SResponseKey;

class SrResponseKeySrRepository extends BaseRepository
{
    public const ACTION_STORE = 'store';
    public const ACTION_RETURN_VALUE = 'return_value';

    public const ALLOWED_ACTIONS = [
        self::ACTION_STORE,
        self::ACTION_RETURN_VALUE,
    ];
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

        foreach ($syncData as $index => $sr) {
            if (empty($sr['id'])) {
                continue;
            }
            if (!empty($sr['action']) && !in_array($sr['action'], self::ALLOWED_ACTIONS)) {
                continue;
            }
            $srId = $sr['id'];
            $saveData = [
                'sr_id' => $srId,
                'sr_response_key_id' => $srResponseKey->id,
            ];
            if (!empty($sr['action'])) {
                $saveData['action'] = $sr['action'];
            }
            if (array_key_exists('single_request', $sr) && is_bool($sr['single_request'])) {
                $saveData['single_request'] = $sr['single_request'];
            }
            if (array_key_exists('disable_request', $sr) && is_bool($sr['disable_request'])) {
                $saveData['disable_request'] = $sr['disable_request'];
            }
            if (!empty($sr['request_response_keys']) && is_array($sr['request_response_keys'])) {
                $saveData['request_response_keys'] = $sr['request_response_keys'];
            }
            if (!empty($sr['response_response_keys']) && is_array($sr['response_response_keys'])) {
                $saveData['response_response_keys'] = $sr['response_response_keys'];
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
