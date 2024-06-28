<?php

namespace App\Services\ApiServices\ServiceRequests;

//use App\Models\ResponseKeyRequestItem;
use App\Exceptions\SrValidationException;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Models\User;
use App\Repositories\SrConfigRepository;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrResponseKeyService extends BaseService
{
    private SrResponseKeyRepository $srResponseKeyRepository;
    private SrResponseKeySrRepository $srResponseKeySrRepository;
    private SResponseKeyRepository $SResponseKeyRepository;
    private SrConfigRepository $srConfigRepository;

    public function __construct(
        private AccessControlService $accessControlService,
        private SrService $srService,
        private SrConfigService $srConfigService
    )
    {
        parent::__construct();
        $this->SResponseKeyRepository = new SResponseKeyRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->srConfigRepository = new SrConfigRepository();
        $this->srResponseKeySrRepository = new SrResponseKeySrRepository();
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->SResponseKeyRepository->setOrderDir($order);
        $this->SResponseKeyRepository->setSortField($sort);
        $this->SResponseKeyRepository->setLimit($count);
        return $this->SResponseKeyRepository->findMany();
    }

    public function findConfigForOperationBySr(Sr $serviceRequest) {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (!$parentServiceRequest instanceof Sr) {
            return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest);
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->config_override)) {
            return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($parentServiceRequest);
        }
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest);
    }


    public function validateSrResponseKeys(Sr $sr, ?bool $requiredOnly = false)
    {
        $responseFormatValue = $this->srConfigService->getConfigValue($sr, DataConstants::RESPONSE_FORMAT);
        $provider = $sr->provider()->first();
        if (empty($responseFormatValue)) {
            throw new SrValidationException(
                sprintf(
                    "Service request (id:%s | name:%s | provider id:%s name: %s) does not have a response_format property/config value.",
                    $sr->id,
                    $sr->name,
                    $provider->id,
                    $provider->name
                )
            );
        }
        return $this->SResponseKeyRepository->createDefaultServiceResponseKeys(
            $sr->s()->first(),
            $responseFormatValue,
            $requiredOnly
        );
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

    public function getRequestResponseKeys(
        Sr $serviceRequest,
    ): LengthAwarePaginator|Collection
    {
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest);
    }

    private function getResponseKeySrsFromRequest($requestData)
    {
        if (!array_key_exists("response_key_srs", $requestData)) {
            return false;
        }
        if (!is_array($requestData["response_key_srs"])) {
            return false;
        }
        $filter = array_filter($requestData["response_key_srs"], function ($item) {
            return (!empty($item["id"]) && is_numeric($item["id"]));
        });
        return array_map(function ($item) {
            return $item["id"];
        }, $filter);
    }

    public function setRequestResponseKeyObject(array $data)
    {
        $fields = [
            "value",
            "show_in_response",
            "list_item",
            "custom_value",
            "append_extra_data_value",
            "prepend_extra_data_value",
            "is_service_request",
            "array_keys",
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

    public function saveSrResponseKeySrs(User $user, Sr $serviceRequest, SrResponseKey $srResponseKey, array $data)
    {
        $provider = $serviceRequest->provider()->first();

        $srIds = $this->getResponseKeySrsFromRequest($data);

        if (!count($srIds)) {
            return false;
        }
        $this->accessControlService->setUser($user);

        $srs = $this->srService->getServiceRequestRepository()->getUserServiceRequestByIds($user, $srIds);

        $srIds = $srs->map(function (Sr $sr) {
            return $sr->id;
        })->toArray();

        $this->srResponseKeySrRepository->saveResponseKeySr(
            $srResponseKey,
            $srIds
        );
        return true;
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
        $srResponseKey = $this->srResponseKeyRepository->getModel();
        if ($srResponseKey->is_service_request) {
            $createResponseKey = $this->saveSrResponseKeySrs(
                $user,
                $serviceRequest,
                $srResponseKey,
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

        $srResponseKey = $this->srResponseKeyRepository->getModel();
        if ($srResponseKey->is_service_request) {
            $save = $this->saveSrResponseKeySrs(
                $user,
                $serviceRequest,
                $srResponseKey,
                $data
            );
        }
        return $save;
    }

    public function updateRequestResponseKey(User $user, Sr $serviceRequest, SrResponseKey $serviceResponseKey, array $data)
    {
        $save = $this->srResponseKeyRepository->updateSrResponseKey(
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
        if (!$save) {
            return false;
        }
        $srResponseKey = $this->srResponseKeyRepository->getModel();
        if ($srResponseKey->is_service_request) {
            $save = $this->saveSrResponseKeySrs(
                $user,
                $serviceRequest,
                $srResponseKey,
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
