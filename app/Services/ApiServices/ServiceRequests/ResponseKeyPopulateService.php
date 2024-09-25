<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\ResponseManager;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ResponseKeyPopulateService
{
    use UserTrait, ErrorTrait;

    private SrRepository $srRepository;
    private SrResponseKeyRepository $srResponseKeyRepository;
    private SResponseKeyRepository $responseKeyRepository;
    private Sr $destSr;
    private S $destService;
    private bool $overwrite = false;

    public function __construct(
        private ApiRequestService $requestOperation,
        private SrConfigService $srConfigService
    )
    {
        $this->srRepository = new SrRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
    }

    public function run(Sr $destSr, array $sourceSrs, ?array $query = []) {
        $destService = $destSr->s()->first();
        if (!$destService) {
            return false;
        }

        $this->destSr = $destSr;
        $this->destService = $destService;

        $this->srRepository->addWhere('id', $sourceSrs, 'in');
        $fetchSourceSrs = $this->srRepository->findMany();

        foreach ($fetchSourceSrs as $sr) {
            $this->handleResponse(
                $sr,
                $this->runSrRequest($sr, $query)
            );
        }

    }

    public function runSrRequest(Sr $sr, ?array $query = []): ApiResponse {
        $provider = $sr->provider()->first();
        $this->requestOperation->setProviderName($provider->name);
        $this->requestOperation->setApiRequestName($sr->name);
        $this->requestOperation->setUser($this->getUser());
        return $this->requestOperation->getOperationRequestContent($query);
    }

    public function handleResponse(Sr $sr, ApiResponse $response): bool {
        return match ($response->getStatus()) {
            'success' => match (ResponseManager::getSrResponseContentType($sr, $response->getResponse())) {
                ResponseManager::CONTENT_TYPE_JSON => $this->handleJsonResponse($sr, $response),
                ResponseManager::CONTENT_TYPE_XML => $this->handleXmlResponse($sr, $response),
                default => false,
            },
            default => false,
        };
    }

    private function handleJsonResponse(Sr $sr, ApiResponse $response) {

        $requestData = $response->getRequestData();
        if (empty($requestData)) {
            return false;
        }

        if (Arr::isList($requestData)) {
            return $this->srTypeHandler($sr, $requestData, 'root_items');
        }
        foreach ($requestData as $key => $value) {
            if (Arr::isList($value) && is_integer($key)) {
                return $this->srTypeHandler($sr, $value, 'root_array');
            }
            if (Arr::isList($value)) {
                return $this->srTypeHandler($sr, $value, $key);
            }
        }
        return false;
    }
    private function handleXmlResponse(Sr $sr, ApiResponse $response) {

        $requestData = $response->getRequestData();
        if (empty($requestData)) {
            return false;
        }
        return $this->srTypeHandler($sr, $response);
    }
    private function srTypeHandler(Sr $sr, array $data, string $itemArrayType): bool {
        return match ($sr->type) {
            SrRepository::SR_TYPE_LIST => $this->populateResponseKeys($data[array_key_first($data)], $itemArrayType),
            SrRepository::SR_TYPE_SINGLE, SrRepository::SR_TYPE_DETAIL => $this->populateResponseKeys($data, $itemArrayType),
            default => false,
        };
    }
    private function populateResponseKeys(array $data, string $itemArrayType): bool {
        if (!Arr::isAssoc($data)) {
            return false;
        }
        $responseKeys = $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $this->destSr
        );
        $itemsArrayResponseKey = $responseKeys->firstWhere('name', 'items_array');
        if ($itemsArrayResponseKey) {
            $this->saveSrResponseKey($itemsArrayResponseKey, $itemArrayType);
        }
        foreach ($data as $key => $value) {
            $responseKey = $responseKeys->firstWhere('name', $key);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toSnake = Str::snake($key);
            $responseKey = $responseKeys->firstWhere('name', $toSnake);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toCamel = Str::camel($key);
            $responseKey = $responseKeys->firstWhere('name', $toCamel);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toSlug = Str::slug($key);
            $responseKey = $responseKeys->firstWhere('name', $toSlug);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $createSResponseKey = $this->responseKeyRepository->createServiceResponseKey(
                $this->destService,
                ['name' => $toSnake]
            );
            if (!$createSResponseKey) {
                continue;
            }
            $this->saveSrResponseKey($this->responseKeyRepository->getModel(), $key);
        }
        return $this->hasErrors();
    }
    private function saveSrResponseKey(SResponseKey $key, string $value): bool {
        $srResponseKey = $key->srResponseKey()->first();
        if ($srResponseKey && !empty($srResponseKey->value) && !$this->overwrite) {
            return false;
        }
        $save = $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $this->destSr,
            $key,
            ['value' => $value]
        );
        if (!$save) {
            $this->addError(
                'error',
                "Error saving sr response key | serviceResponseKey: {$key->name} | srResponseKeyValue: {$value}"
            );
            return false;
        }
        return true;
    }

    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }

}
