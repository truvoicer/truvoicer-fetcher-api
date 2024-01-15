<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SResponseKey;
use Illuminate\Database\Eloquent\Relations\HasOne;

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


    public function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $keyName)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->srResponseKey()
            ->where('name', $keyName)
            ->first();
    }

    public function removeAllServiceRequestResponseKeys(Sr $serviceRequest) {
        return $serviceRequest->srResponseKey()->delete();
    }

    public function duplicateRequestResponseKeys(Sr $sourceServiceRequest,
                                                 Sr $destinationServiceRequest) {
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
                                             Sr $destinationServiceRequest) {
//        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
//        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function saveRequestResponseKey(SrResponseKey $serviceRequestResponseKey, array $data) {
        $this->setModel($serviceRequestResponseKey);
        return $this->save($data);
    }

    public function findSrResponseKeysWithRelation(Sr $serviceRequest)
    {
        $service = $serviceRequest->s()->first()->sResponseKey();
        return $service->with('srResponseKey')
            ->get();
    }
    public function findServiceRequestResponseKeys(Sr $serviceRequest, string $sort = "name", string $order = "asc", ?int $count = null) {
        return $serviceRequest->srResponseKeys()
            ->with('srResponseKey')
            ->orderBy($sort, $order)
            ->paginate();
    }
    public function findServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey) {
        return $serviceRequest->srResponseKeys()
            ->where('s_response_key_id', '=', $serviceResponseKey->id)
            ->with('srResponseKey')
            ->first();
    }
    public function saveServiceRequestResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey, array $data) {
        $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
//        dd($find);
        if (!$find instanceof Sr) {
            return $this->dbHelpers->validateToggle(
                $serviceRequest->srResponseKeys()->toggle([$serviceResponseKey->id => $data]),
                [$serviceResponseKey->id]
            );
        }

        $update = $serviceRequest->srResponseKeys()->updateExistingPivot($serviceResponseKey->id, $data);
        return true;
    }

    public function deleteServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey) {
        return ($serviceRequest->srResponseKeys()->detach($serviceResponseKey->id) > 0);
    }

    public function deleteRequestResponseKeys(SrResponseKey $requestResponseKey) {
        $this->setModel($requestResponseKey);
        return $this->delete();
    }
}
