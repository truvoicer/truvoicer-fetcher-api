<?php

namespace App\Repositories;

use App\Helpers\Tools\UtilHelpers;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\Category\CategoryService;

class SrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Sr::class);
    }

    public function getModel(): Sr
    {
        return parent::getModel();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function getServiceRequestByProvider(Provider $provider, string $sort, string $order, ?int $count = null) {
        return $provider->serviceRequest()
            ->orderBy($sort, $order)->get();
    }

    public function getRequestByName(Provider $provider, string $serviceRequestName) {
        return $provider->serviceRequest()
            ->where('name', $serviceRequestName)
            ->first();
    }

    private function buildSaveData(array $data)
    {
        $fields = [
            'name',
            'label',
            'pagination_type',
            'service_id',
            'category_id',
        ];
        $saveData = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }
        return $saveData;
    }
    public function createServiceRequest(Provider $provider, array $data) {
        $create = $provider->serviceRequest()->create($this->buildSaveData($data));
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
    public function saveServiceRequest(Sr $serviceRequest, array $data) {
        $this->setModel($serviceRequest);
        return $this->save($this->buildSaveData($data));
    }
    public function duplicateServiceRequest(Sr $serviceRequest, array $data)
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
//            $serviceRequestConfig->setValueType($item->value_type);
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
