<?php

namespace App\Repositories;

use App\Models\ServiceRequestResponseKey;

class ServiceRequestResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestResponseKey::class);
    }

    public function getRequestResponseKeyByName(Provider $provider, ServiceRequest $serviceRequest, string $keyName)
    {
        foreach ($serviceRequest->getServiceRequestResponseKeys() as $serviceRequestResponseKey) {
            if ($serviceRequestResponseKey->getServiceResponseKey()->getKeyValue() === $keyName) {
                return $serviceRequestResponseKey;
            }
        }
        return null;
    }

    public function removeAllServiceRequestResponseKeys(ServiceRequest $serviceRequest) {
        $requestResponseKeys = $serviceRequest->getServiceRequestResponseKeys();
        foreach ($requestResponseKeys as $responseKey) {
            $serviceRequest->removeServiceRequestResponseKey($responseKey);
        }
        $this->getEntityManager()->persist($serviceRequest);
        $this->getEntityManager()->flush();
    }

    public function duplicateRequestResponseKeys(ServiceRequest $sourceServiceRequest,
                                                 ServiceRequest $destinationServiceRequest) {
        $sourceResponseKeys = $sourceServiceRequest->getServiceRequestResponseKeys();
        foreach ($sourceResponseKeys as $item) {
            $responseKey = new ServiceRequestResponseKey();
            $responseKey->setServiceRequest($destinationServiceRequest);
            $responseKey->setServiceResponseKey($item->getServiceResponseKey());
            $responseKey->setResponseKeyValue($item->getResponseKeyValue());
            $responseKey->setShowInResponse($item->getShowInResponse());
            $responseKey->setHasArrayValue($item->getHasArrayValue());
            $responseKey->setArrayKeys($item->getArrayKeys());
            $responseKey->setListItem($item->getListItem());
            $responseKey->setReturnDataType($item->getReturnDataType());
            $responseKey->setAppendExtraData($item->getAppendExtraData());
            $responseKey->setAppendExtraDataValue($item->getAppendExtraDataValue());
            $responseKey->setPrependExtraData($item->getPrependExtraData());
            $responseKey->setPrependExtraDataValue($item->getPrependExtraDataValue());
            $responseKey->setIsServiceRequest($item->getIsServiceRequest());
            $destinationServiceRequest->addServiceRequestResponseKey($responseKey);
            $this->getEntityManager()->persist($responseKey);
        }
        $this->getEntityManager()->persist($destinationServiceRequest);
        $this->getEntityManager()->flush();
    }

    public function mergeRequestResponseKeys(ServiceRequest $sourceServiceRequest,
                                             ServiceRequest $destinationServiceRequest) {
        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function saveRequestResponseKey(ServiceRequestResponseKey $requestResponseKey) {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $this->getEntityManager()->persist($requestResponseKey);
            $this->getEntityManager()->flush();
            return $requestResponseKey;
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function deleteRequestResponseKeys(ServiceRequestResponseKey $requestResponseKey) {
        $this->getEntityManager()->remove($requestResponseKey);
        $this->getEntityManager()->flush();
        return $requestResponseKey;
    }
}
