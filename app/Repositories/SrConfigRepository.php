<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrConfig;

class SrConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrConfig::class);
    }

    public function getModel(): SrConfig
    {
        return parent::getModel();
    }

    public function findByParams(Sr $serviceRequest, string $sort, string $order, ?int $count = null)
    {
        return $serviceRequest->srConfig()
            ->orderBy($sort, $order)
            ->get();
    }

    public function getRequestConfigByName(Sr $serviceRequest, string $configItemName)
    {
        return $serviceRequest
            ->srConfig()
            ->where('name', $configItemName)
            ->first();
    }

    public function createRequestConfig(Sr $serviceRequest, array $data)
    {
        $create = $serviceRequest->srConfig()->create($data);
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
}
