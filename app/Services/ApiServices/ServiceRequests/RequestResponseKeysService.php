<?php
namespace App\Services\ApiServices\ServiceRequests;

//use App\Models\ResponseKeyRequestItem;
use App\Models\Service;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponseKey;
use App\Models\ServiceResponseKey;
use App\Library\Defaults\DefaultData;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceRequestResponseKeyRepository;
use App\Repositories\ServiceResponseKeyRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestResponseKeysService extends BaseService
{
    private ServiceRepository $serviceRepository;
    private ServiceRequestRepository $serviceRequestRepository;
    private ServiceRequestResponseKeyRepository $requestResponseKeyRepository;
    private ServiceResponseKeyRepository $responseKeyRepository;
    private $defaultRequestResponseKeyData = [
        "response_key_value" => "",
        "show_in_response" => false,
        "list_item" => false,
        "append_extra_data" => false,
        "append_extra_data_value" => "",
        "prepend_extra_data" => false,
        "prepend_extra_data_value" => "",
        "is_service_request" => false,
        "has_array_value" => false,
    ];
    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->responseKeyRepository = new ServiceResponseKeyRepository();
        $this->requestResponseKeyRepository = new ServiceRequestResponseKeyRepository();
//        $this->responseKeyRequestItemRepo = $this->entityManager->getRepository(ResponseKeyRequestItem::class);
    }

    public function findByParams(string $sort, string $order, int $count) {
        $this->responseKeyRepository->setOrderDir($order);
        $this->responseKeyRepository->setSortField($sort);
        $this->responseKeyRepository->setLimit($count);
        return $this->responseKeyRepository->findMany();
    }

    public function getResponseKeyById(int $responseKeyId) {
        $responseKey = $this->responseKeyRepository->findById($responseKeyId);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Response key id:%s not found in database.",
                $responseKeyId
            ));
        }
        return $responseKey;
    }

    public function getRequestResponseKeyById(int $requestResponseKeyId) {
        $requestResponseKey = $this->requestResponseKeyRepository->findById($requestResponseKeyId);
        if ($requestResponseKey === null) {
            throw new BadRequestHttpException(sprintf("Request response key id:%s not found in database.",
                $requestResponseKeyId
            ));
        }
        return $requestResponseKey;
    }

    private function getServiceResponseKeysObject(Service $service, array $data)
    {
        $responseKeyData = [];
        $responseKeyData['service_id'] = $service->id;
        $responseKeyData['name'] = $data['name'];
        $responseKeyData['value'] = $data['value'];
        return $responseKeyData;
    }
//    private function getResponseKeyRequestItemObject(ResponseKeyRequestItem $responseKeyRequestItem,
//                                                     ServiceRequestResponseKey $requestResponseKey, $serviceRequestId)
//    {
//        $serviceRequest = $this->serviceRequestRepository->findById($serviceRequestId);
//        $responseKeyRequestItem->setServiceRequest(
//            $serviceRequest
//        );
//        $responseKeyRequestItem->setServiceRequestResponseKey($requestResponseKey);
//        return $responseKeyRequestItem;
//    }

    public function getResponseKeysByServiceId(int $serviceId) {
        $service = $this->serviceRepository->findById($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id:%s not found.", $serviceId));
        }
        return $this->responseKeyRepository->findById($service->id);
    }

    public function createDefaultServiceResponseKeys(Service $service) {
        foreach (DefaultData::getServiceResponseKeys() as $keyName => $keyValue) {
            $this->createServiceResponseKeys([
               "service_id" => $service->id,
               "key_name" => $keyName,
               "key_value" => $keyValue
            ]);
        }
    }

    public function createServiceResponseKeys(array $data)
    {
        $service = $this->serviceRepository->findById($data["service_id"]);
        $responseKey = $this->getServiceResponseKeysObject($service, $data);
        return $this->responseKeyRepository->save($responseKey);
    }

    public function updateServiceResponseKeys(array $data)
    {
        $getResponseKey = $this->responseKeyRepository->findById($data["id"]);
        $service = $this->serviceRepository->findById($data["service_id"]);
        $responseKey = $this->getServiceResponseKeysObject($service, $data);
        $responseKey['id'] = $getResponseKey->id;
        return $this->responseKeyRepository->save($responseKey);
    }

    public function deleteServiceResponseKey(int $id) {
        $responseKey = $this->responseKeyRepository->findById($id);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Service response key id: %s not found in database.", $id));
        }
        $this->responseKeyRepository->setModel($responseKey);
        return $this->responseKeyRepository->delete();
    }

    public function getRequestResponseKeyObjectById(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey) {
        return $this->getRequestResponseKey($serviceRequest, $serviceResponseKey);
    }

    public function getRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $responseKey) {
        $getRequestResponseKey = $this->responseKeyRepository->getRequestResponseKey($serviceRequest, $responseKey);
        if ($getRequestResponseKey === null) {
            return [
                "service_response_key" => $responseKey
            ];
        }
        return $getRequestResponseKey;
    }

    public function getRequestResponseKeys(ServiceRequest $serviceRequest, string $sort = "key_name", string $order = "asc", int $count = null) {
        $service = $serviceRequest->service()->first();
        if (!$service instanceof Service) {
            throw new BadRequestHttpException(sprintf("Service request id:%s not found in database.",
                $serviceRequest->id
            ));
        }
        $responseKeys = $service->responseKey()->get()->toArray();
        return array_map(function ($responseKey) use($serviceRequest) {
            return $this->getRequestResponseKey($serviceRequest, $responseKey);
        }, $responseKeys);
    }


    public function getServiceResponseKeyByName(Service $service, string $responseKeyName)
    {
        return $this->responseKeyRepository->getServiceResponseKeyByName($service, $responseKeyName);
    }
    public function getRequestResponseKeyByName(Provider $provider, ServiceRequest $serviceRequest, string $configItemName)
    {
        return $this->requestResponseKeyRepository->getRequestResponseKeyByName($provider, $serviceRequest, $configItemName);
    }

    private function getServiceRequestIdFromRequest($requestData) {
        if (!array_key_exists("response_key_request_item", $requestData)) {
            throw new BadRequestHttpException("response_key_request_item not in request");
        }
        if (array_key_exists("service_request", $requestData["response_key_request_item"])) {
            return $requestData['response_key_request_item']["service_request"]["id"];
        } elseif (array_key_exists("value", $requestData["response_key_request_item"])) {
            return $requestData['response_key_request_item']["value"];
        }
        return null;
    }

    public function setRequestResponseKeyObject(array $data) {
        $responseKeyData = array_merge($this->defaultRequestResponseKeyData, $data);
        $requestResponseKeyData = [];
        $requestResponseKeyData['response_key_value'] = $responseKeyData['response_key_value'];
        $requestResponseKeyData['show_in_response'] = $responseKeyData['show_in_response'];
        $requestResponseKeyData['list_item'] = $responseKeyData['list_item'];
        $requestResponseKeyData['append_extra_data'] = $responseKeyData['append_extra_data'];
        $requestResponseKeyData['append_extra_data_value'] = $responseKeyData['append_extra_data_value'];
        $requestResponseKeyData['prepend_extra_data'] = $responseKeyData['prepend_extra_data'];
        $requestResponseKeyData['prepend_extra_data_value'] = $responseKeyData['prepend_extra_data_value'];
        $requestResponseKeyData['is_service_request'] = $responseKeyData['is_service_request'];
        $requestResponseKeyData['has_array_value'] = $responseKeyData['has_array_value'];

//        if ($responseKeyData['is_service_request']) {
//            $serviceRequestId = $this->getServiceRequestIdFromRequest($responseKeyData);
//            if ($serviceRequestId !== null) {
//                $getResponseKeyRequestItem = $requestResponseKey->getResponseKeyRequestItem();
//                if ($getResponseKeyRequestItem === null) {
//                    $getResponseKeyRequestItem = new ResponseKeyRequestItem();
//                }
//                $responseKeyRequestItem = $this->getResponseKeyRequestItemObject(
//                    $getResponseKeyRequestItem,
//                    $requestResponseKey,
//                    $serviceRequestId
//                );
//                $requestResponseKey->setResponseKeyRequestItem($responseKeyRequestItem);
//            }
//        }

        $requestResponseKeyData['array_keys'] = null;
        if(!empty($responseKeyData['array_keys']) && is_array($responseKeyData['array_keys'])) {
            $requestResponseKeyData['array_keys'] = $responseKeyData['array_keys'];
        }
        if(!empty($responseKeyData['return_data_type'])) {
            $requestResponseKeyData['return_data_type'] = $responseKeyData['return_data_type'];
        }
        return $requestResponseKeyData;
    }

    public function createRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, array $data) {
        return $this->requestResponseKeyRepository->saveServiceRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function updateRequestResponseKey(ServiceRequestResponseKey $serviceResponseKey, array $data) {
        return $this->requestResponseKeyRepository->saveRequestResponseKey(
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function deleteRequestResponseKey(ServiceRequestResponseKey $serviceRequestResponseKey) {
        $this->requestResponseKeyRepository->setModel($serviceRequestResponseKey);
        return $this->requestResponseKeyRepository->delete();
    }

    public function deleteRequestResponseKeyByServiceResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey) {
        $this->requestResponseKeyRepository->addWhere("service_request", $serviceRequest->id);
        $this->requestResponseKeyRepository->addWhere("service_response_key", $serviceResponseKey->id);
        $requestResponseKey = $this->requestResponseKeyRepository->findOne();
        if ($requestResponseKey !== null) {
            return $this->requestResponseKeyRepository->deleteRequestResponseKeys($requestResponseKey);
        }
        throw new BadRequestHttpException(
            sprintf("Error deleting property value. (Service request id:%s, Response key id:%s)",
                $requestResponseKey, $serviceResponseKey
            ));
    }

    public function getRequestResponseKeyRepository(): ServiceRequestResponseKeyRepository
    {
        return $this->requestResponseKeyRepository;
    }
}
