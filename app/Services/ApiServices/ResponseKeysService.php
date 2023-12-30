<?php
namespace App\Services\ApiServices;

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
use App\Services\Tools\HttpRequestService;
use App\Helpers\Tools\UtilHelpers;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResponseKeysService extends BaseService
{
    private ServiceRepository $serviceRepository;
    private ServiceRequestRepository $serviceRequestRepository;
    private ServiceRequestResponseKeyRepository $requestKeysRepo;
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
        $this->requestKeysRepo = new ServiceRequestResponseKeyRepository();
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
        $requestResponseKey = $this->requestKeysRepo->findById($requestResponseKeyId);
        if ($requestResponseKey === null) {
            throw new BadRequestHttpException(sprintf("Request response key id:%s not found in database.",
                $requestResponseKeyId
            ));
        }
        return $requestResponseKey;
    }

    private function getServiceResponseKeysObject(ServiceResponseKey $responseKeys, Service $service, array $data)
    {
        $responseKeys->setService($service);
        $responseKeys->setKeyName(UtilHelpers::labelToName($data["key_name"], true));
        $responseKeys->setKeyValue(UtilHelpers::labelToName($data["key_name"]));
        return $responseKeys;
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

    public function getResponseKeysByService(Service $service) {
        return $this->responseKeyRepository->findServiceResponseKeys($service);
    }
    public function getResponseKeysByServiceId(int $serviceId) {
        $service = $this->serviceRepository->findById($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException("Service id:%s not found.". $serviceId);
        }
        $this->responseKeyRepository->addWhere("service_id", $service->id);
        return $this->responseKeyRepository->findOne();
    }

    public function getResponseKeysByServiceName(string $serviceName) {
        $this->responseKeyRepository->addWhere("name", $serviceName);
        $service = $this->serviceRepository->findOne();
        if ($service === null) {
            throw new BadRequestHttpException("Service name:%s not found.". $serviceName);
        }
        $this->responseKeyRepository->addWhere("service_id", $service->id);
        return $this->responseKeyRepository->findOne();
    }

    public function createDefaultServiceResponseKeys(Model $service) {
        $errors = [];
        foreach (DefaultData::getServiceResponseKeys() as $keyName => $keyValue) {
            $create = $this->createServiceResponseKeys([
               "service_id" => $service->id,
               "key_name" => $keyName,
               "key_value" => $keyValue
            ]);
            if (!$create) {
                $errors[] = sprintf("Error creating default response key: %s", $keyName);
            }
        }
        return count($errors) === 0;
    }

    public function createServiceResponseKeys(Service $service, array $data)
    {
        return $this->responseKeyRepository->createServiceResponseKey($service, $data);
    }

    public function updateServiceResponseKeys(ServiceResponseKey $serviceResponseKey, array $data)
    {
        return $this->responseKeyRepository->saveServiceResponseKey($serviceResponseKey, $data);
    }

    public function deleteServiceResponseKeyById(int $id) {
        $responseKey = $this->responseKeyRepository->findById($id);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Service response key id: %s not found in database.", $id));
        }
        $this->responseKeyRepository->setModel($responseKey);
        return $this->responseKeyRepository->delete();
    }

    public function deleteServiceResponseKey(ServiceResponseKey $serviceResponseKey) {
        $this->responseKeyRepository->setModel($serviceResponseKey);
        return $this->responseKeyRepository->delete();
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

    public function getServiceResponseKeyByName(Service $service, string $responseKeyName)
    {
        return $this->responseKeyRepository->getServiceResponseKeyByName($service, $responseKeyName);
    }
    public function getRequestResponseKeyByName(Provider $provider, ServiceRequest $serviceRequest, string $configItemName)
    {
        return $this->requestKeysRepo->getRequestResponseKeyByName($provider, $serviceRequest, $configItemName);
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

    public function setRequestResponseKeyObject(ServiceRequestResponseKey $requestResponseKey,
                                                ServiceRequest $serviceRequest,
                                                ServiceResponseKey $responseKey, array $data) {
        $responseKeyData = array_merge($this->defaultRequestResponseKeyData, $data);
        $requestResponseKey->setServiceRequest($serviceRequest);
        $requestResponseKey->setServiceResponseKey($responseKey);
        $requestResponseKey->setResponseKeyValue($responseKeyData['response_key_value']);
        $requestResponseKey->setShowInResponse($responseKeyData['show_in_response']);
        $requestResponseKey->setListItem($responseKeyData['list_item']);

        $requestResponseKey->setAppendExtraData($responseKeyData['append_extra_data']);
        $requestResponseKey->setAppendExtraDataValue($responseKeyData['append_extra_data_value']);
        $requestResponseKey->setPrependExtraData($responseKeyData['prepend_extra_data']);
        $requestResponseKey->setPrependExtraDataValue($responseKeyData['prepend_extra_data_value']);

        $requestResponseKey->setIsServiceRequest($responseKeyData['is_service_request']);
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

        $requestResponseKey->setHasArrayValue($responseKeyData['has_array_value']);
        if((array_key_exists("array_keys", $responseKeyData) && is_array($responseKeyData['array_keys'])) ||
            (array_key_exists("array_keys", $responseKeyData) && $responseKeyData['array_keys'] === null)
        ) {
            $requestResponseKey->setArrayKeys($responseKeyData['array_keys']);
        } else {

            $requestResponseKey->setArrayKeys(null);
        }
        if(array_key_exists("return_data_type", $responseKeyData) && isset($responseKeyData['return_data_type']) && $responseKeyData['return_data_type'] !== null) {
            $requestResponseKey->setReturnDataType($responseKeyData['return_data_type']);
        }
        return $requestResponseKey;
    }

    public function getResponseKeyRepository(): ServiceResponseKeyRepository
    {
        return $this->responseKeyRepository;
    }
}
