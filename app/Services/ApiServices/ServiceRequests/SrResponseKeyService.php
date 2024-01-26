<?php

namespace App\Services\ApiServices\ServiceRequests;

//use App\Models\ResponseKeyRequestItem;
use App\Models\S;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrResponseKey;
use App\Models\SResponseKey;
use App\Library\Defaults\DefaultData;
use App\Models\User;
use App\Repositories\SrConfigRepository;
use App\Repositories\SRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrResponseKeyService extends BaseService
{
    private SRepository $serviceRepository;
    private SrRepository $srRepository;
    private SrResponseKeyRepository $srResponseKeyRepository;
    private SrResponseKeySrRepository $srResponseKeySrRepository;
    private SResponseKeyRepository $SResponseKeyRepository;
    private SrConfigRepository $srConfigRepository;
    private AccessControlService $accessControlService;

    public function __construct(
        AccessControlService $accessControlService
    )
    {
        parent::__construct();
        $this->serviceRepository = new SRepository();
        $this->srRepository = new SrRepository();
        $this->SResponseKeyRepository = new SResponseKeyRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->srConfigRepository = new SrConfigRepository();
        $this->srResponseKeySrRepository = new SrResponseKeySrRepository();
        $this->accessControlService = $accessControlService;
//        $this->responseKeyRequestItemRepo = $this->entityManager->getRepository(ResponseKeyRequestItem::class);
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->SResponseKeyRepository->setOrderDir($order);
        $this->SResponseKeyRepository->setSortField($sort);
        $this->SResponseKeyRepository->setLimit($count);
        return $this->SResponseKeyRepository->findMany();
    }

    public function getResponseKeyById(int $responseKeyId)
    {
        $responseKey = $this->SResponseKeyRepository->findById($responseKeyId);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Response key id:%s not found in database.",
                $responseKeyId
            ));
        }
        return $responseKey;
    }

    public function getRequestResponseKeyById(int $requestResponseKeyId)
    {
        $requestResponseKey = $this->srResponseKeyRepository->findById($requestResponseKeyId);
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

    public function getResponseKeysByServiceId(int $serviceId)
    {
        $service = $this->serviceRepository->findById($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id:%s not found.", $serviceId));
        }
        return $this->SResponseKeyRepository->findById($service->id);
    }

    public function validateSrResponseKeys(Sr $sr, ?bool $requiredOnly = false)
    {
        $configItem = $this->srConfigRepository->getRequestConfigByName($sr, 'content_type');
        if (!$configItem instanceof SrConfig) {
            $this->addError(
                'validation_sr_r_key_content_type_not_found',
                sprintf("Service request id:%s does not have a content_type config item.", $sr->id)
            );
            return false;
        }
        return $this->SResponseKeyRepository->createDefaultServiceResponseKeys(
            $sr->s()->first(),
            $configItem->value,
            $requiredOnly
        );
    }

    public function createDefaultServiceResponseKeys(S $service)
    {
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
        return $this->SResponseKeyRepository->save($responseKey);
    }

    public function updateServiceResponseKeys(array $data)
    {
        $getResponseKey = $this->SResponseKeyRepository->findById($data["id"]);
        $service = $this->serviceRepository->findById($data["service_id"]);
        $responseKey = $this->getServiceResponseKeysObject($service, $data);
        $responseKey['id'] = $getResponseKey->id;
        return $this->SResponseKeyRepository->save($responseKey);
    }

    public function deleteServiceResponseKey(int $id)
    {
        $responseKey = $this->SResponseKeyRepository->findById($id);
        if ($responseKey === null) {
            throw new BadRequestHttpException(sprintf("Service response key id: %s not found in database.", $id));
        }
        $this->SResponseKeyRepository->setModel($responseKey);
        return $this->SResponseKeyRepository->delete();
    }

    public function getRequestResponseKeyObjectById(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        return $this->getRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
    }

    public function getRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $responseKey)
    {
        $getRequestResponseKey = $this->srResponseKeyRepository->findServiceRequestResponseKeyByResponseKey(
            $serviceRequest,
            $responseKey
        );
        if (!$getRequestResponseKey instanceof SResponseKey) {
            return false;
        }
        return $getRequestResponseKey;
    }

    public function getRequestResponseKeys(Sr $serviceRequest, string $sort = "name", string $order = "asc", ?int $count = null)
    {
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest, $sort, $order, $count);
    }


    public function getServiceResponseKeyByName(S $service, string $responseKeyName)
    {
        return $this->SResponseKeyRepository->getServiceResponseKeyByName($service, $responseKeyName);
    }

    public function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $configItemName)
    {
        return $this->srResponseKeyRepository::getRequestResponseKeyByName($provider, $serviceRequest, $configItemName);
    }

    private function getServiceRequestIdFromRequest($requestData)
    {
        if (!array_key_exists("response_key_request_item", $requestData)) {
            return false;
        }
        if (array_key_exists("value", $requestData["response_key_request_item"])) {
            return $requestData['response_key_request_item']["value"];
        }
        return false;
    }

    public function setRequestResponseKeyObject(array $data)
    {
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

        $requestResponseKeyData['array_keys'] = null;
        if (!empty($responseKeyData['array_keys']) && !is_array($responseKeyData['array_keys'])) {
            throw new BadRequestHttpException("array_keys must be an array");
        }
        return $requestResponseKeyData;
    }

    public function saveSrResponseKeySrValue(User $user, SrResponseKey $srResponseKey, array $data)
    {
        $serviceRequestId = $this->getServiceRequestIdFromRequest($data);
        if (!$serviceRequestId) {
            return false;
        }
        $serviceRequest = $this->srRepository->findById($serviceRequestId);
        if (!$serviceRequest instanceof Sr) {
            return false;
        }

        $this->accessControlService->setUser($user);
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $serviceRequest->provider()->first(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return false;
        }
        return $this->srResponseKeySrRepository->saveResponseKeySr(
            $srResponseKey,
            [$serviceRequestId]
        );
    }

    public function createSrResponseKey(User $user, Sr $serviceRequest, string $sResponseKeyName, array $data)
    {
        $createResponseKey = $this->srResponseKeyRepository->createServiceRequestResponseKey(
            $serviceRequest,
            $sResponseKeyName,
            $this->setRequestResponseKeyObject($data)
        );
        if (!$createResponseKey) {
            return false;
        }

        if (!empty($data['is_service_request']) && $data['is_service_request'] === true) {
            $createResponseKey = $this->saveSrResponseKeySrValue(
                $user,
                $this->srResponseKeyRepository->getModel(),
                $data
            );
        }
        return $createResponseKey;
    }

    public function saveSrResponseKey(User $user, Sr $serviceRequest, SResponseKey $serviceResponseKey, array $data)
    {
        $save = $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
        if (!$save) {
            return false;
        }

        if (!empty($data['is_service_request']) && $data['is_service_request'] === true) {
            $save = $this->saveSrResponseKeySrValue(
                $user,
                $this->srResponseKeyRepository->getModel(),
                $data
            );
        }
        return $save;
    }

    public function updateRequestResponseKey(User $user, SrResponseKey $serviceResponseKey, array $data)
    {
        $save = $this->srResponseKeyRepository->updateSrResponseKey(
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
        if (!$save) {
            return false;
        }
        if (!empty($data['is_service_request']) && $data['is_service_request'] === true) {
            $save = $this->saveSrResponseKeySrValue(
                $user,
                $this->srResponseKeyRepository->getModel(),
                $data
            );
        }
        return $save;
    }

    public function deleteRequestResponseKey(SrResponseKey $serviceRequestResponseKey)
    {
        $this->srResponseKeyRepository->setModel($serviceRequestResponseKey);
        return $this->srResponseKeyRepository->delete();
    }

    public function deleteRequestResponseKeyByServiceResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        $this->srResponseKeyRepository->addWhere("service_request", $serviceRequest->id);
        $this->srResponseKeyRepository->addWhere("service_response_key", $serviceResponseKey->id);
        $requestResponseKey = $this->srResponseKeyRepository->findOne();
        if ($requestResponseKey !== null) {
            return $this->srResponseKeyRepository->deleteRequestResponseKeys($requestResponseKey);
        }
        throw new BadRequestHttpException(
            sprintf("Error deleting property value. (Service request id:%s, Response key id:%s)",
                $requestResponseKey, $serviceResponseKey
            ));
    }

    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service request response key ids provided.");
        }
        return $this->srResponseKeyRepository->deleteBatch($ids);
    }

    public function getSrResponseKeyRepository(): SrResponseKeyRepository
    {
        return $this->srResponseKeyRepository;
    }
}
