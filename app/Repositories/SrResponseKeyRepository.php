<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SResponseKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrResponseKey::class);
    }

    public function getModel(): SrResponseKey
    {
        return parent::getModel();
    }


    public static function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $keyName)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->srResponseKeys()
            ->with('srResponseKey')
            ->where('name', $keyName)
            ->first();
    }

    public static function getSrResponseKeyValueByName(Provider $provider, Sr $serviceRequest, string $keyName)
    {
        $responseKey = self::getRequestResponseKeyByName($provider, $serviceRequest, $keyName);
        if (!$responseKey instanceof SResponseKey) {
            return null;
        }
        $value = $responseKey->srResponseKey()->first();
        if (!$value instanceof SrResponseKey) {
            return null;
        }
        return $value->value;
    }

    public function removeAllServiceRequestResponseKeys(Sr $serviceRequest)
    {
        return $serviceRequest->srResponseKey()->delete();
    }

    public function duplicateRequestResponseKeys(Sr $sourceServiceRequest,
                                                 Sr $destinationServiceRequest)
    {
//        $sourceResponseKeys = $sourceServiceRequest->getServiceRequestResponseKeys();
//        foreach ($sourceResponseKeys as $item) {
//            $responseKey = new ServiceRequestResponseKey();
//            $responseKey->setServiceRequest($destinationServiceRequest);
//            $responseKey->setServiceResponseKey($item->getServiceResponseKey());
//            $responseKey->setResponseKeyValue($item->value);
//            $responseKey->setShowInResponse($item->getShowInResponse());
//            $responseKey->setHasArrayValue($item->getHasArrayValue());
//            $responseKey->setArrayKeys($item->getArrayKeys());
//            $responseKey->setListItem($item->getListItem());
//            $responseKey->setReturnDataType($item->getReturnDataType());
//            $responseKey->setAppendExtraData($item->getAppendExtraData());
//            $responseKey->setAppendExtraDataValue($item->getAppendExtraDataValue());
//            $responseKey->setPrependExtraData($item->getPrependExtraData());
//            $responseKey->setPrependExtraDataValue($item->getPrependExtraDataValue());
//            $responseKey->setIsServiceRequest($item->is_service_request);
//            $destinationServiceRequest->addServiceRequestResponseKey($responseKey);
//            $this->getEntityManager()->persist($responseKey);
//        }
//        $this->getEntityManager()->persist($destinationServiceRequest);
//        $this->getEntityManager()->flush();
        return null;
    }

    public function mergeRequestResponseKeys(Sr $sourceServiceRequest,
                                             Sr $destinationServiceRequest)
    {
//        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
//        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function updateSrResponseKey(SrResponseKey $serviceRequestResponseKey, array $data)
    {
        $this->setModel($serviceRequestResponseKey);
        return $this->save($data);
    }

    public function findSrResponseKeysWithRelationQuery(Sr $serviceRequest, ?array $excludeKeys = [], ?array $conditions = []): HasMany
    {
        $service = $serviceRequest->s()->first()->sResponseKey();
        $service->with(['srResponseKey' => function ($query) use ($serviceRequest) {
            $query->where('sr_id', '=', $serviceRequest->id);
        }]);
        foreach ($conditions as $key => $value) {
            $service->where($key, '=', $value);
        }
        if (!empty($excludeKeys)) {
            $service->whereNotIn('name', $excludeKeys);
        }
        return $service;
    }
    public function findSrResponseKeysWithRelation(Sr $serviceRequest, ?array $excludeKeys = [], ?array $conditions = []): LengthAwarePaginator|Collection
    {
        return $this->getResults(
            $this->findSrResponseKeysWithRelationQuery($serviceRequest, $excludeKeys, $conditions)
        );
    }
    public function findOneSrResponseKeysWithRelation(Sr $serviceRequest, ?array $excludeKeys = [], ?array $conditions = []): Model|null
    {
        return $this->findSrResponseKeysWithRelationQuery($serviceRequest, $excludeKeys, $conditions)->first();
    }

    public function findServiceRequestResponseKeys(Sr $serviceRequest, string $sort = "name", string $order = "asc", ?int $count = null)
    {
        return $serviceRequest->srResponseKeys()
            ->with('srResponseKey')
            ->orderBy($sort, $order)
            ->paginate();
    }

    public function findServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        $query = SResponseKey::with(['srResponseKey' => function ($query) use ($serviceRequest) {
            $query->where('sr_id', '=', $serviceRequest->id);
            $query->with('srResponseKeySrs', function ($query) use ($serviceRequest){
//                $query->where('sr_id', '=', $serviceRequest->id);
            });
        }])
            ->where('id', '=', $serviceResponseKey->id)
            ->first();
        return $query;
    }

    public function createServiceRequestResponseKey(Sr $serviceRequest, string $sResponseKeyName, array $data)
    {
        $sResponseKerRepo = new SResponseKeyRepository();
        $findSResponseKey = $sResponseKerRepo->getServiceResponseKeyByName(
            $serviceRequest->s()->first(),
            $sResponseKeyName
        );

        if ($findSResponseKey instanceof SResponseKey) {
            return $this->saveServiceRequestResponseKey(
                $serviceRequest,
                $findSResponseKey,
                $data
            );
        }
        $sResponseKey = $sResponseKerRepo->createServiceResponseKey(
            $serviceRequest->s()->first(),
            ['name' => $sResponseKeyName]
        );
        if (!$sResponseKey) {
            return false;
        }
        return $this->saveServiceRequestResponseKey(
            $serviceRequest,
            $sResponseKerRepo->getModel(),
            $data
        );
    }

    public function saveServiceRequestResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey, array $data)
    {
        $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
        if (!$find->srResponseKey instanceof SrResponseKey) {
            $toggle = $this->dbHelpers->validateToggle(
                $serviceRequest->srResponseKeys()->toggle([$serviceResponseKey->id => $data]),
                [$serviceResponseKey->id]
            );
            $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
            $this->setModel($find->srResponseKey);
        }
        $this->setModel($find->srResponseKey);
        $update = $serviceRequest->srResponseKeys()->updateExistingPivot($serviceResponseKey->id, $data);
        return true;
    }

    public function deleteServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        return ($serviceRequest->srResponseKeys()->detach($serviceResponseKey->id) > 0);
    }

    public function deleteRequestResponseKeys(SrResponseKey $requestResponseKey)
    {
        $this->setModel($requestResponseKey);
        return $this->delete();
    }
}
