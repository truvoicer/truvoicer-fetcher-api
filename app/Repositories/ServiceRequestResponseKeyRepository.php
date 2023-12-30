<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;
use App\Models\ServiceResponseKey;

class ServiceRequestResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestResponseKey::class);
    }

    public function getModel(): ServiceRequestResponseKey
    {
        return parent::getModel();
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

    public function mergeRequestResponseKeys(ServiceRequest $sourceServiceRequest,
                                             ServiceRequest $destinationServiceRequest) {
//        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
//        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function saveRequestResponseKey(ServiceRequestResponseKey $serviceRequestResponseKey, array $data) {
        $this->setModel($serviceRequestResponseKey);
        return $this->save($data);
    }

    public function findServiceRequestResponseKeys(ServiceRequest $serviceRequest) {
        return $serviceRequest->serviceRequestResponseKeys()
            ->with('serviceRequestServiceResponseKey')
            ->get();
    }
    public function findServiceRequestResponseKeyByResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey) {
        return $serviceRequest->serviceRequestResponseKeys()
            ->where('service_response_key_id', '=', $serviceResponseKey->id)
            ->with('serviceRequestServiceResponseKey')
            ->first();
    }
    public function saveServiceRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, array $data) {
        $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);

        if (!$find instanceof ServiceResponseKey) {
            return $this->dbHelpers->validateToggle(
                $serviceRequest->serviceRequestResponseKeys()->toggle([$serviceResponseKey->id => $data]),
                [$serviceResponseKey->id]
            );
        }

        $update = $serviceRequest->serviceRequestResponseKeys()->updateExistingPivot($serviceResponseKey->id, $data);
        return true;
    }

    public function deleteServiceRequestResponseKeyByResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey) {
        return ($serviceRequest->serviceRequestResponseKeys()->detach($serviceResponseKey->id) > 0);
    }

    public function deleteRequestResponseKeys(ServiceRequestResponseKey $requestResponseKey) {
        $this->setModel($requestResponseKey);
        return $this->delete();
    }
}
