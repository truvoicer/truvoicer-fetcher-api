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
    private ApiResponse $response;

    private bool $overwrite = false;
    private array $findItemsArray = [];
    private array $score = [];

    public function __construct(
        private ApiRequestService $requestOperation,
        private SrConfigService   $srConfigService
    )
    {
        $this->srRepository = new SrRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
    }

    public function run(Sr $destSr, array $sourceSrs, ?array $query = [])
    {
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

    public function runSrRequest(Sr $sr, ?array $query = []): ApiResponse
    {
        $provider = $sr->provider()->first();
        $this->requestOperation->setProviderName($provider->name);
        $this->requestOperation->setApiRequestName($sr->name);
        $this->requestOperation->setUser($this->getUser());
        return $this->requestOperation->getOperationRequestContent($query);
    }

    public function handleResponse(Sr $sr, ApiResponse $response): bool
    {
        $this->score = [];
        $this->response = $response;
        return match ($response->getStatus()) {
            'success' => match (ResponseManager::getSrResponseContentType($sr, $response->getResponse())) {
                ResponseManager::CONTENT_TYPE_JSON => $this->handleJsonResponse($sr),
                ResponseManager::CONTENT_TYPE_XML => $this->handleXmlResponse($sr),
                default => false,
            },
            default => false,
        };
    }

    private function handleJsonResponse(Sr $sr)
    {

        $requestData = $this->response->getRequestData();
        if (empty($requestData)) {
            return false;
        }

        if (Arr::isList($requestData)) {
            return $this->srTypeHandler($sr, $requestData, 'root_items');
        }

        $this->prepareItemsArrayScoreData($requestData);
        $itemsArrayValue = $this->getItemsArrayValueFromScoreData($this->score);
        dd($itemsArrayValue);
//        return $this->srTypeHandler($sr, $value, $itemsArrayValue);
        return false;
    }

    private function getItemsArrayValueFromScoreData(array $scoreData): ?string
    {
        if (empty($scoreData)) {
            return null;
        }
        arsort($scoreData);
        $value = array_keys($scoreData)[0];
        if (is_integer($value)) {
            return 'root_array';
        } elseif (is_string($value)) {
            return $value;
        }
        return '';
    }

    private function findByKeyTree(array $data, ?array $requestData = []): mixed
    {
        array_pop($data);
        foreach ($data as $value) {
            array_shift($data);
            if (!isset($requestData[$value])) {
                return $requestData;
            }
            $requestData = $requestData[$value];
        }
        return $requestData;
    }

    private function prepareItemsArrayScoreData(array $data, ?array $parent = []): void
    {
        $parentKey = (array_key_exists('key', $parent)) ? $parent['key'] : null;

        if (!array_key_exists($parentKey, $this->score)) {
            $this->score[$parentKey] = 0;
        }

        foreach ($data as $key => $value) {
            $parent['key'] = $key;
            if (!array_key_exists('parent', $parent)) {
                $parent['parent'] = [];
            }
            $parent['parent'][] = $key;
            if (!is_array($value)) {
                continue;
            }
            if (Arr::isAssoc($value)) {
                $parentData = $this->findByKeyTree($parent['parent'], $this->response->getRequestData());
                foreach ($value as $valKey => $val) {
                    foreach ($parentData as $values) {
                        if (!is_array($values)) {
                            continue;
                        }
                        if (array_key_exists($valKey, $values)) {
                            $this->score[$parentKey]++;
                        }
                    }
                }
            }
            $this->prepareItemsArrayScoreData($value, $parent);
        }
    }

    private function handleXmlResponse(Sr $sr)
    {
        $requestData = $this->response->getRequestData();
        if (empty($requestData)) {
            return false;
        }
        return $this->srTypeHandler($sr);
    }

    private function srTypeHandler(Sr $sr, array $data, string $itemArrayType): bool
    {
        return match ($sr->type) {
            SrRepository::SR_TYPE_LIST => $this->populateResponseKeys($data[array_key_first($data)], $itemArrayType),
            SrRepository::SR_TYPE_SINGLE, SrRepository::SR_TYPE_DETAIL => $this->populateResponseKeys($data, $itemArrayType),
            default => false,
        };
    }

    private function populateResponseKeys(array $data, string $itemArrayType): bool
    {
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

    private function saveSrResponseKey(SResponseKey $key, string $value): bool
    {
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
