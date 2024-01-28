<?php

namespace App\Repositories;

use App\Helpers\Tools\UtilHelpers;
use App\Models\Category;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrRateLimit;
use App\Services\Category\CategoryService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrRateLimitRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrRateLimit::class);
    }

    public function getModel(): SrRateLimit
    {
        return parent::getModel();
    }

    public function buildData(array $data)
    {
        $newData = [];
        foreach (SrRateLimit::FIELDS as $key) {
            if (array_key_exists($key, $data)) {
                $newData[$key] = $data[$key];
            }
        }
        return $newData;
    }

    public function findRateLimitBySr(Sr $serviceRequest)
    {
        return $serviceRequest->srRateLimit()->first();
    }

    public function createSrRateLimit(Sr $sr, array $data = []): bool
    {
        $create = $sr->srRateLimit()->create($this->buildData($data));
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
    public function saveSrRateLimit(SrRateLimit $srRateLimit, array $data = []): bool
    {
        $this->setModel($srRateLimit);
        return $srRateLimit->save($this->buildData($data));
    }

    public function deleteSrRateLimit(SrRateLimit $srRateLimit): bool
    {
        $this->setModel($srRateLimit);
        return $srRateLimit->delete();
    }
}
