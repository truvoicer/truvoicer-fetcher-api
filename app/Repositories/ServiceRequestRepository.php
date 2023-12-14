<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;

class ServiceRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequest::class);
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function getServiceRequestByProvider(Provider $provider, string $sort, string $order, int $count) {
        $this->addWhere('provider_id', $provider);
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function getRequestByName(Provider $provider, string $serviceRequestName) {
        return $provider->serviceRequest()
            ->where('name', $serviceRequestName)
            ->first();
    }

    public function duplicateServiceRequest(ServiceRequest $serviceRequest, array $data)
    {
//        $requestResponseKeysRepo = $this->getEntityManager()->getRepository(ServiceRequestResponseKey::class);
//
//        $newServiceRequest = new ServiceRequest();
//        $newServiceRequest->setServiceRequestName($data['item_name']);
//        $newServiceRequest->setServiceRequestLabel($data['item_label']);
//        if (!empty($data['item_pagination_type'])) {
//            $newServiceRequest->setPaginationType($data['item_pagination_type']);
//        }
//        $newServiceRequest->setService($serviceRequest->getService());
//        $newServiceRequest->setCategory($serviceRequest->getCategory());
//        $newServiceRequest->setProvider($serviceRequest->getProvider());
//
//        $requestResponseKeysRepo->duplicateRequestResponseKeys($serviceRequest, $newServiceRequest);
//
//        $requestConfig = $serviceRequest->getServiceRequestConfigs();
//        foreach ($requestConfig as $item) {
//            $serviceRequestConfig = new ServiceRequestConfig();
//            $serviceRequestConfig->setItemName($item->getItemName());
//            $serviceRequestConfig->setItemValue($item->getItemValue());
//            $serviceRequestConfig->setValueType($item->getValueType());
//            $serviceRequestConfig->setServiceRequest($newServiceRequest);
//            $newServiceRequest->addServiceRequestConfig($serviceRequestConfig);
//            $this->getEntityManager()->persist($serviceRequestConfig);
//        }
//
//        $requestParams = $serviceRequest->getServiceRequestParameters();
//        foreach ($requestParams as $item) {
//            $serviceRequestParams = new ServiceRequestParameter();
//            $serviceRequestParams->setParameterName($item->getParameterName());
//            $serviceRequestParams->setParameterValue($item->getParameterValue());
//            $serviceRequestParams->setServiceRequest($newServiceRequest);
//            $newServiceRequest->addServiceRequestParameter($serviceRequestParams);
//            $this->getEntityManager()->persist($serviceRequestParams);
//        }
//        $this->getEntityManager()->persist($newServiceRequest);
//        $this->getEntityManager()->flush();
//        return $serviceRequest;
        return null;
    }
}
