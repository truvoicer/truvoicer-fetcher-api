<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\ResponseKeyRequestItem;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestResponseKeysService extends BaseService
{
    private $entityManager;
    private $httpRequestService;
    private $serviceRepository;
    private $serviceRequestRepository;
    private $requestKeysRepo;
    private $responseKeyRepository;
    private $responseKeyRequestItemRepo;
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
    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->responseKeyRepository = new ServiceResponseKeyRepository();
        $this->requestKeysRepo = new ServiceRequestResponseKeyRepository();
//        $this->responseKeyRequestItemRepo = $this->entityManager->getRepository(ResponseKeyRequestItem::class);
    }

    public function findByParams(string $sort, string $order, int $count) {
        $this->responseKeyRepository->setOrderBy($order);
        $this->responseKeyRepository->setSort($sort);
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
        $responseKeys->setKeyName($data['key_name']);
        $responseKeys->setKeyValue($data['key_value']);
        return $responseKeys;
    }
    private function getResponseKeyRequestItemObject(ResponseKeyRequestItem $responseKeyRequestItem,
                                                     ServiceRequestResponseKey $requestResponseKey, $serviceRequestId)
    {
        $serviceRequest = $this->serviceRequestRepository->findById($serviceRequestId);
        $responseKeyRequestItem->setServiceRequest(
            $serviceRequest
        );
        $responseKeyRequestItem->setServiceRequestResponseKey($requestResponseKey);
        return $responseKeyRequestItem;
    }

    public function getResponseKeysByServiceId(int $serviceId) {
        $service = $this->serviceRepository->findBy(["id" => $serviceId]);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id:%s not found.". $serviceId));
        }
        return $this->responseKeyRepository->findBy(["service" => $service]);
    }

    public function createDefaultServiceResponseKeys(Service $service) {
        foreach (DefaultData::getServiceResponseKeys() as $keyName => $keyValue) {
            $this->createServiceResponseKeys([
               "service_id" => $service->getId(),
               "key_name" => $keyName,
               "key_value" => $keyValue
            ]);
        }
    }

    public function createServiceResponseKeys(array $data)
    {
        $service = $this->serviceRepository->findById($data["service_id"]);
        $responseKey = $this->getServiceResponseKeysObject(new ServiceResponseKey(), $service, $data);
        if ($this->httpRequestService->validateData($responseKey)) {
            return $this->responseKeyRepository->save($responseKey);
        }
        return false;
    }

    public function updateServiceResponseKeys(array $data)
    {
        $getResponseKey = $this->responseKeyRepository->findById($data["id"]);
        $service = $this->serviceRepository->findById($data["service_id"]);
        $responseKey = $this->getServiceResponseKeysObject($getResponseKey, $service, $data);
        if ($this->httpRequestService->validateData($responseKey)) {
            return $this->responseKeyRepository->save($responseKey);
        }
        return false;
    }

    public function deleteServiceResponseKey(int $id) {
        $responseKey = $this->responseKeyRepository->findById($id);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Service response key id: %s not found in database.", $id));
        }
        return $this->responseKeyRepository->delete($responseKey);
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
        $responseKeys = $this->responseKeyRepository->findBy(["service" => $serviceRequest->getService()]);
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
        if ($responseKeyData['is_service_request']) {
            $serviceRequestId = $this->getServiceRequestIdFromRequest($responseKeyData);
            if ($serviceRequestId !== null) {
                $getResponseKeyRequestItem = $requestResponseKey->getResponseKeyRequestItem();
                if ($getResponseKeyRequestItem === null) {
                    $getResponseKeyRequestItem = new ResponseKeyRequestItem();
                }
                $responseKeyRequestItem = $this->getResponseKeyRequestItemObject(
                    $getResponseKeyRequestItem,
                    $requestResponseKey,
                    $serviceRequestId
                );
                $requestResponseKey->setResponseKeyRequestItem($responseKeyRequestItem);
            }
        }

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

    public function createRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, array $data) {
        $setRequestResponseKey = $this->setRequestResponseKeyObject(
            new ServiceRequestResponseKey(),
            $serviceRequest, $serviceResponseKey, $data);
        return $this->requestKeysRepo->saveRequestResponseKey($setRequestResponseKey);
    }

    public function updateRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey, array $data) {
        $this->requestKeysRepo->addWhere("service_request", $serviceRequest->getId());
        $this->requestKeysRepo->addWhere("service_response_key", $serviceResponseKey->getId());
        $requestResponseKey = $this->requestKeysRepo->findOne();

        if ($requestResponseKey !== null) {
            $setRequestResponseKey = $this->setRequestResponseKeyObject(
                $requestResponseKey,
                $requestResponseKey->getServiceRequest(),
                $requestResponseKey->getServiceResponseKey(),
                $data
            );
            return $this->requestKeysRepo->saveRequestResponseKey($setRequestResponseKey);
        }
        $setRequestResponseKey = $this->setRequestResponseKeyObject(
            new ServiceRequestResponseKey(),
            $serviceRequest, $serviceResponseKey, $data);
        return $this->requestKeysRepo->saveRequestResponseKey($setRequestResponseKey);
    }

    public function deleteRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $serviceResponseKey) {
        $this->requestKeysRepo->addWhere("service_request", $serviceRequest->getId());
        $this->requestKeysRepo->addWhere("service_response_key", $serviceResponseKey->getId());
        $requestResponseKey = $this->requestKeysRepo->findOne();
        if ($requestResponseKey !== null) {
            return $this->requestKeysRepo->deleteRequestResponseKeys($requestResponseKey);
        }
        throw new BadRequestHttpException(
            sprintf("Error deleting property value. (Service request id:%s, Response key id:%s)",
                $requestResponseKey, $serviceResponseKey
            ));
    }
}
