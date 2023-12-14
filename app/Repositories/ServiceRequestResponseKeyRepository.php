<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;

class ServiceRequestResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestResponseKey::class);
    }

    public function getRequestResponseKeyByName(Provider $provider, ServiceRequest $serviceRequest, string $keyName)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->serviceRequestResponseKey()
            ->where('name', $keyName)
            ->first();
    }

    public function removeAllServiceRequestResponseKeys(ServiceRequest $serviceRequest) {
        return $serviceRequest->serviceRequestResponseKey()->delete();
    }

    public function duplicateRequestResponseKeys(ServiceRequest $sourceServiceRequest,
                                                 ServiceRequest $destinationServiceRequest) {
//        $sourceResponseKeys = $sourceServiceRequest->getServiceRequestResponseKeys();
//        foreach ($sourceResponseKeys as $item) {
//            $responseKey = new ServiceRequestResponseKey();
//            $responseKey->setServiceRequest($destinationServiceRequest);
//            $responseKey->setServiceResponseKey($item->getServiceResponseKey());
//            $responseKey->setResponseKeyValue($item->getResponseKeyValue());
//            $responseKey->setShowInResponse($item->getShowInResponse());
//            $responseKey->setHasArrayValue($item->getHasArrayValue());
//            $responseKey->setArrayKeys($item->getArrayKeys());
//            $responseKey->setListItem($item->getListItem());
//            $responseKey->setReturnDataType($item->getReturnDataType());
//            $responseKey->setAppendExtraData($item->getAppendExtraData());
//            $responseKey->setAppendExtraDataValue($item->getAppendExtraDataValue());
//            $responseKey->setPrependExtraData($item->getPrependExtraData());
//            $responseKey->setPrependExtraDataValue($item->getPrependExtraDataValue());
//            $responseKey->setIsServiceRequest($item->getIsServiceRequest());
//            $destinationServiceRequest->addServiceRequestResponseKey($responseKey);
//            $this->getEntityManager()->persist($responseKey);
//        }
//        $this->getEntityManager()->persist($destinationServiceRequest);
//        $this->getEntityManager()->flush();
        return null;
    }

    public function mergeRequestResponseKeys(ServiceRequest $sourceServiceRequest,
                                             ServiceRequest $destinationServiceRequest) {
//        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
//        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function saveRequestResponseKey(array $data) {
        return $this->save($data);
    }

    public function deleteRequestResponseKeys(ServiceRequestResponseKey $requestResponseKey) {
        $this->setModel($requestResponseKey);
        return $this->delete();
    }
}
