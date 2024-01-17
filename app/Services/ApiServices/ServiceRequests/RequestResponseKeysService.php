<?php
namespace App\Services\ApiServices\ServiceRequests;

//use App\Models\ResponseKeyRequestItem;
use App\Models\S;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SResponseKey;
use App\Library\Defaults\DefaultData;
use App\Repositories\SRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SResponseKeyRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestResponseKeysService extends BaseService
{
    private SRepository $serviceRepository;
    private SrRepository $serviceRequestRepository;
    private SrResponseKeyRepository $requestResponseKeyRepository;
    private SResponseKeyRepository $responseKeyRepository;
    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = new SRepository();
        $this->serviceRequestRepository = new SrRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
        $this->requestResponseKeyRepository = new SrResponseKeyRepository();
//        $this->responseKeyRequestItemRepo = $this->entityManager->getRepository(ResponseKeyRequestItem::class);
    }

    public function findByParams(string $sort, string $order, int $count = -1) {
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

    private function getServiceResponseKeysObject(S $service, array $data)
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

    public function createDefaultServiceResponseKeys(S $service) {
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

    public function getRequestResponseKeyObjectById(Sr $serviceRequest, SResponseKey $serviceResponseKey) {
        return $this->getRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
    }

    public function getRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $responseKey) {
        $getRequestResponseKey = $this->requestResponseKeyRepository->findServiceRequestResponseKeyByResponseKey(
            $serviceRequest,
            $responseKey
        );
        if (!$getRequestResponseKey instanceof SResponseKey ) {
            return false;
        }
        return $getRequestResponseKey;
    }

    public function getRequestResponseKeys(Sr $serviceRequest, string $sort = "name", string $order = "asc", ?int $count = null) {
        return $this->requestResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest, $sort, $order, $count);
    }


    public function getServiceResponseKeyByName(S $service, string $responseKeyName)
    {
        return $this->responseKeyRepository->getServiceResponseKeyByName($service, $responseKeyName);
    }
    public function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $configItemName)
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
        $fields = [
            "value",
            "show_in_response",
            "list_item",
            "append_extra_data",
            "append_extra_data_value",
            "prepend_extra_data",
            "prepend_extra_data_value",
            "is_service_request",
            "has_array_value",
            "array_keys",
            "return_data_type"
        ];
        $requestResponseKeyData = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $requestResponseKeyData[$field] = $data[$field];
            }
        }

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
        if(!empty($responseKeyData['array_keys']) && !is_array($responseKeyData['array_keys'])) {
            throw new BadRequestHttpException("array_keys must be an array");
        }
        return $requestResponseKeyData;
    }

    public function createSrResponseKey(Sr $serviceRequest, string $sResponseKeyName, array $data) {
        return $this->requestResponseKeyRepository->createServiceRequestResponseKey(
            $serviceRequest,
            $sResponseKeyName,
            $this->setRequestResponseKeyObject($data)
        );
    }
    public function saveSrResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey, array $data) {
        return $this->requestResponseKeyRepository->saveServiceRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function updateRequestResponseKey(SrResponseKey $serviceResponseKey, array $data) {
        return $this->requestResponseKeyRepository->updateSrResponseKey(
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function deleteRequestResponseKey(SrResponseKey $serviceRequestResponseKey) {
        $this->requestResponseKeyRepository->setModel($serviceRequestResponseKey);
        return $this->requestResponseKeyRepository->delete();
    }

    public function deleteRequestResponseKeyByServiceResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey) {
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

    public function getRequestResponseKeyRepository(): SrResponseKeyRepository
    {
        return $this->requestResponseKeyRepository;
    }
}
