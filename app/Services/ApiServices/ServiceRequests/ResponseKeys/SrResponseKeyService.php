<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys;

//use App\Models\ResponseKeyRequestItem;

use Truvoicer\TruFetcherGet\Enums\Property\PropertyType;
use Truvoicer\TruFetcherGet\Exceptions\SrValidationException;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SResponseKey;
use Truvoicer\TruFetcherGet\Models\SrResponseKey;
use Truvoicer\TruFetcherGet\Models\SrResponseKeySr;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\SrConfigRepository;
use Truvoicer\TruFetcherGet\Repositories\SResponseKeyRepository;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeyRepository;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
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
    ) {
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

    public function findParentSrForResponseKeys(Sr $serviceRequest)
    {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (!$parentServiceRequest instanceof Sr) {
            return $serviceRequest;
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->response_key_override)) {
            return $parentServiceRequest;
        }
        return $serviceRequest;
    }

    public function findResponseKeysForOperationBySr(Sr $serviceRequest, ?array $excludeKeys = [], ?array $conditions = [])
    {
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $this->findParentSrForResponseKeys($serviceRequest),
            $excludeKeys
        );
    }


    public function validateSrResponseKeys(Sr $sr, ?bool $requiredOnly = false): bool
    {

        $providerService = app(ProviderService::class);

        $provider = $sr->provider()->first();
        /** @var \App\Models\Provider|null $provider */
        $entityProvider = $providerService->getProviderEntityFromProviderProperties(
            $provider
        );

        if (!$entityProvider) {
            $responseFormatValue = $this->srConfigService->getConfigValue($sr, PropertyType::RESPONSE_FORMAT->value);

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
        } else {
            $responseFormatValue = $providerService->getProviderPropertyValue(
                $entityProvider,
                PropertyType::RESPONSE_FORMAT->value
            );
            if (empty($responseFormatValue)) {
                throw new SrValidationException(
                    sprintf(
                        "Provider (id:%s | name:%s) does not have a response_format property/config value.",
                        $entityProvider->id,
                        $entityProvider->name
                    )
                );
            }
        }
        $s = $sr->s()->first();
        if (!$s) {
            throw new SrValidationException(
                sprintf(
                    "This sr (id: %s | name: %s | label: %s) has not been assigned a service.",
                    $sr->id,
                    $sr->name,
                    $sr->label,
                )
            );
        }

        return $this->SResponseKeyRepository->createDefaultServiceResponseKeys(
            $s,
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
        array $excludeKeys = [],
        array $conditions = [],
        ?string $orderBy = null,
        ?string $orderDirection = 'asc'
    ): LengthAwarePaginator|Collection {
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $serviceRequest,
            $excludeKeys,
            $conditions,
            $orderBy,
            $orderDirection
        );
    }

    private function validateSrResponseKeySrRequestData(array $requestData)
    {
        $newData = [];
        if (
            empty($requestData["sr_id"]) ||
            !is_numeric($requestData["sr_id"])
        ) {
            throw new SrValidationException("Invalid service request id provided.");
        }
        $newData['sr_id'] = (int)$requestData["sr_id"];

        if (
            !empty($requestData["action"]) &&
            !in_array($requestData["action"], SrResponseKeySrRepository::ALLOWED_ACTIONS)
        ) {
            throw new SrValidationException("Invalid action provided.");
        }
        $newData['action'] = $requestData["action"];

        if (!empty($requestData["request_response_keys"]) && is_array($requestData["request_response_keys"])) {
            $newData['request_response_keys'] = $requestData["request_response_keys"];
        }

        if (!empty($requestData["response_response_keys"]) && is_array($requestData["response_response_keys"])) {
            $newData['response_response_keys'] = $requestData["response_response_keys"];
        }

        if (!array_key_exists('single_request', $requestData)) {
            throw new SrValidationException("Invalid single_request provided.");
        }
        if (!is_bool($requestData["single_request"])) {
            throw new SrValidationException("single_request must be a boolean.");
        }
        $newData['single_request'] = $requestData["single_request"];

        if (!array_key_exists('disable_request', $requestData)) {
            throw new SrValidationException("Invalid disable_request provided.");
        }
        if (!is_bool($requestData["disable_request"])) {
            throw new SrValidationException("disable_request must be a boolean.");
        }
        $newData['disable_request'] = $requestData["disable_request"];

        return $newData;
    }

    public function setRequestResponseKeyObject(array $data)
    {
        $fields = [
            "value",
            "show_in_response",
            "searchable",
            "list_item",
            "custom_value",
            "search_priority",
            "is_date",
            "date_format",
            "append_extra_data_value",
            "prepend_extra_data_value",
            "array_keys",
        ];
        $requestResponseKeyData = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $requestResponseKeyData[$field] = $data[$field];
            }
        }

        return $requestResponseKeyData;
    }

    public function updateSrResponseKeySr(User $user, SrResponseKeySr $srResponseKeySr, array $data)
    {
        return $this->srResponseKeySrRepository->updateSrResponseKeySrs(
            $user,
            $srResponseKeySr,
            $data
        );
    }

    public function saveSrResponseKeySrs(User $user, array $data): bool
    {
        return $this->srResponseKeySrRepository->storeSrResponseKeySrs(
            $user,
            $data
        );
    }

    public function createSrResponseKey(User $user, Sr $serviceRequest, string $sResponseKeyName, array $data)
    {
        return $this->srResponseKeyRepository->createServiceRequestResponseKey(
            $serviceRequest,
            $sResponseKeyName,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function saveSrResponseKey(User $user, Sr $serviceRequest, SResponseKey $serviceResponseKey, array $data)
    {
        return $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $serviceRequest,
            $serviceResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function updateRequestResponseKey(User $user, Sr $serviceRequest, SrResponseKey $srResponseKey, array $data)
    {
        if (!empty($data["name"])) {
            $sResponseKey = $srResponseKey->sResponseKey()->first();
            if (
                $sResponseKey instanceof SResponseKey &&
                !$this->SResponseKeyRepository->saveServiceResponseKey(
                    $sResponseKey,
                    ['name' => $data["name"]]
                )
            ) {
                return false;
            }
        }
        return $this->srResponseKeyRepository->updateSrResponseKey(
            $srResponseKey,
            $this->setRequestResponseKeyObject($data)
        );
    }

    public function deleteRequestResponseKey(SrResponseKey $serviceRequestResponseKey)
    {
        $this->srResponseKeyRepository->setModel($serviceRequestResponseKey);
        return $this->srResponseKeyRepository->delete();
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
