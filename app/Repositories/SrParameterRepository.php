<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrParameter;

class SrParameterRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrParameter::class);
    }

    public function getModel(): SrParameter
    {
        return parent::getModel();
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $this->getResults(
            $serviceRequest->srParameter()
        );
    }
    public function findByParams(Sr $serviceRequest, string $sort, string $order, ?int $count = null)
    {
        return $serviceRequest->srParameter()
            ->orderBy($sort, $order)
            ->paginate($count);
    }

    public function getRequestParametersByRequestName(Provider $provider, string $serviceRequestName = null)
    {
        return $provider->serviceRequest()
            ->where('name', $serviceRequestName)
            ->first()
            ->srParameter()
            ->get();
    }
    public function createRequestParameter(Sr $serviceRequest, array $data)
    {
        $create = $serviceRequest->srParameter()->create($data);
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
}
