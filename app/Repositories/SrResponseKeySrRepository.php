<?php

namespace App\Repositories;

use App\Exceptions\SrValidationException;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use App\Models\SResponseKey;
use App\Models\User;

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

    public function prepareSaveData(array $data)
    {
        $saveData = [];
        if (!empty($data['action'])) {
            $saveData['action'] = $data['action'];
        }
        if (array_key_exists('single_request', $data) && is_bool($data['single_request'])) {
            $saveData['single_request'] = $data['single_request'];
        }
        if (array_key_exists('disable_request', $data) && is_bool($data['disable_request'])) {
            $saveData['disable_request'] = $data['disable_request'];
        }
        if (!empty($data['request_response_keys']) && is_array($data['request_response_keys'])) {
            $saveData['request_response_keys'] = $data['request_response_keys'];
        }
        if (!empty($data['response_response_keys']) && is_array($data['response_response_keys'])) {
            $saveData['response_response_keys'] = $data['response_response_keys'];
        }
        if (!empty($data['sr_id'])) {
            $saveData['sr_id'] = (int)$data['sr_id'];
        }
        if (!empty($data['sr_response_key_id'])) {
            $saveData['sr_response_key_id'] = (int)$data['sr_response_key_id'];
        }

        return $saveData;
    }
    public function validateSaveDataSr(User $user, int $srId): bool {
        $srRepo = new SrRepository();

        $srs = $srRepo->getUserServiceRequestByIds(
            $user,
            [$srId]
        );
        if ($srs->where('id', $srId)->count() === 0) {
            throw new SrValidationException("Service request not found or access denied.");
        }
        return true;
    }

    public function storeSrResponseKeySrs(User $user, array $data)
    {
        if (empty($data['sr_id'])) {
            return false;
        }
        if (!empty($data['action']) && !in_array($data['action'], self::ALLOWED_ACTIONS)) {
            return false;
        }
        $saveData = $this->prepareSaveData($data);
        if (!$this->validateSaveDataSr($user, (int)$saveData['sr_id'])) {
            return false;
        }
        $srResponseKeySr = new SrResponseKeySr();
        $srResponseKeySr->fill($saveData);
        return $srResponseKeySr->save();
    }
    public function updateSrResponseKeySrs(User $user, SrResponseKeySr $srResponseKeySr, array $data)
    {
        $saveData = $this->prepareSaveData($data);
        if (!empty($saveData['sr_id']) && !$this->validateSaveDataSr($user, (int)$saveData['sr_id'])) {
            return false;
        }
        return $srResponseKeySr->update($saveData);
    }
}
