<?php
namespace App\Services\ApiServices;

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
use App\Services\Tools\HttpRequestService;
use App\Helpers\Tools\UtilHelpers;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SResponseKeysService extends BaseService
{
    const RESPONSE_KEY_REQUIRED = "required";
    const RESPONSE_KEY_NAME = "name";
    private SRepository $serviceRepository;
    private SrRepository $serviceRequestRepository;
    private SrResponseKeyRepository $requestKeysRepo;
    private SResponseKeyRepository $responseKeyRepository;
    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = new SRepository();
        $this->serviceRequestRepository = new SrRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
        $this->requestKeysRepo = new SrResponseKeyRepository();
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
        $requestResponseKey = $this->requestKeysRepo->findById($requestResponseKeyId);
        if ($requestResponseKey === null) {
            throw new BadRequestHttpException(sprintf("Request response key id:%s not found in database.",
                $requestResponseKeyId
            ));
        }
        return $requestResponseKey;
    }

    private function getServiceResponseKeysObject(SResponseKey $responseKeys, S $service, array $data)
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

    public function getResponseKeysByService(S $service) {
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

    public function createDefaultServiceResponseKeys(S $service, ?string $contentType = 'json', ?bool $requiredOnly = false) {
        return $this->responseKeyRepository->createDefaultServiceResponseKeys($service, $contentType, $requiredOnly);
    }

    public function createServiceResponseKeys(S $service, array $data)
    {
        return $this->responseKeyRepository->createServiceResponseKey($service, $data);
    }

    public function updateServiceResponseKeys(SResponseKey $serviceResponseKey, array $data)
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

    public function deleteServiceResponseKey(SResponseKey $serviceResponseKey) {
        $this->responseKeyRepository->setModel($serviceResponseKey);
        return $this->responseKeyRepository->delete();
    }

    public function getRequestResponseKey(Sr $serviceRequest, SResponseKey $responseKey) {
        $getRequestResponseKey = $this->responseKeyRepository->getRequestResponseKey($serviceRequest, $responseKey);
        if ($getRequestResponseKey === null) {
            return [
                "service_response_key" => $responseKey
            ];
        }
        return $getRequestResponseKey;
    }

    public function getServiceResponseKeyByName(S $service, string $responseKeyName)
    {
        return $this->responseKeyRepository->getServiceResponseKeyByName($service, $responseKeyName);
    }
    public function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $configItemName)
    {
        return $this->requestKeysRepo::getRequestResponseKeyByName($provider, $serviceRequest, $configItemName);
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

    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service ids provided.");
        }
        return $this->serviceRepository->deleteBatch($ids);
    }

    public function getResponseKeyRepository(): SResponseKeyRepository
    {
        return $this->responseKeyRepository;
    }
}
