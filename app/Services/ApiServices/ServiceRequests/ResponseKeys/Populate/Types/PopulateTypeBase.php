<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\PopulateTrait;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\Tools\XmlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PopulateTypeBase
{
    use UserTrait, ErrorTrait, PopulateTrait;

    protected SrRepository $srRepository;
    protected SrResponseKeyRepository $srResponseKeyRepository;
    private SResponseKeyRepository $responseKeyRepository;

    protected Sr $destSr;
    protected S $destService;
    protected ApiResponse $response;

    protected array $findItemsArray = [];
    protected array $score = [];

    public function __construct(
        protected ApiRequestService $requestOperation,
        protected SrConfigService   $srConfigService,
        protected ResponseHandler   $responseHandler
    )
    {
        $this->srRepository = new SrRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
    }

    public function populate(Sr $destSr, Sr $sourceSr, ?array $query = [])
    {
        $destService = $destSr->s()->first();
        if (!$destService) {
            return false;
        }

        $this->destSr = $destSr;
        $this->destService = $destService;

        $this->handleResponse(
            $sourceSr,
            $this->runSrRequest($sourceSr, $query)
        );

    }


    protected function parseDataArrayValue(array $data, ?array $keys = []): array
    {
        return array_keys(Arr::dot($data));
    }

    protected function getParsedKeyItem(string $key, mixed $value, ?bool $attribute = false): array
    {
        return [
            'key' => $key,
            'type' => gettype($value),
            'attribute' => $attribute
        ];
    }

    protected function parseResponseKey(array $data): array
    {
        $keys = [];
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $keys[] = $this->getParsedKeyItem($key, $value);
                continue;
            }
            if (is_array($value)) {
                if (array_key_exists('xml_value_type', $value) && $value['xml_value_type'] === 'attribute') {
                    if (is_array($value['attributes'])) {
                        foreach ($this->parseDataArrayValue($value['attributes']) as $item) {
                            $keys[] = $this->getParsedKeyItem(
                                sprintf(
                                    '%s.attributes.%s',
                                    $key,
                                    $item
                                ),
                                Arr::get($value['attributes'], $item),
                                true
                            );
                        }
                    }
                    if (is_array($value['values'])) {
                        foreach ($this->parseDataArrayValue($value['values']) as $item) {
                            $keys[] = $this->getParsedKeyItem(
                                sprintf(
                                    '%s.%s',
                                    $key,
                                    $item
                                ),
                                Arr::get($value['values'], $item),
                                true
                            );
                        }
                    } else {
                        $keys[] = $this->getParsedKeyItem(
                            "{$key}.values",
                            $value['values'],
                            true
                        );
                    }
                    continue;
                }
                foreach ($this->parseDataArrayValue($value) as $item) {
                    $keys[] = $this->getParsedKeyItem(
                        sprintf(
                            '%s.%s',
                            $key,
                            $item
                        ),
                        Arr::get($value, $item)
                    );
                }
            }
        }
        return $keys;
    }
    private function removeStringFromKey(string $string, string $haystack): string
    {
        $split = explode('.', $haystack);
        $findAttIndex = array_search($string, $split);
        if ($findAttIndex !== false) {
            unset($split[$findAttIndex]);
            $haystack = implode('.', $split);
        }
        return $haystack;
    }
    protected function populateResponseKeys(array $data): bool
    {
        if (!Arr::isAssoc($data)) {
            return false;
        }
        $responseKeys = $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $this->destSr,
        );
        $parsedData = $this->parseResponseKey($data);

        $srResponseKeySaveData = [
            'list_item' => true,
            'show_in_response' => true
        ];
        foreach ($parsedData as $item) {
            $key = $item['key'];
            $type = $item['type'];

            $responseKey = $responseKeys->firstWhere('name', $key);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key, $srResponseKeySaveData);
                continue;
            }
            $toSnake = Str::snake($key);
            $responseKey = $responseKeys->firstWhere('name', $toSnake);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key, $srResponseKeySaveData);
                continue;
            }
            $toCamel = Str::camel($key);
            $responseKey = $responseKeys->firstWhere('name', $toCamel);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key, $srResponseKeySaveData);
                continue;
            }
            $toSlug = Str::slug($key);
            $responseKey = $responseKeys->firstWhere('name', $toSlug);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key, $srResponseKeySaveData);
                continue;
            }
            if ($item['attribute']) {
                $toSnake = $this->removeStringFromKey('attributes', $toSnake);
                $toSnake = $this->removeStringFromKey('values', $toSnake);
            }
            $buildName = str_replace(
                ['.', ' ', '-'],
                '_',
                $toSnake
            );
            $responseKey = $responseKeys->firstWhere('name', $buildName);
            if (!$responseKey) {
                $createSResponseKey = $this->responseKeyRepository->createServiceResponseKey(
                    $this->destService,
                    ['name' => $buildName]
                );
                if (!$createSResponseKey) {
                    continue;
                }
                $responseKey = $this->responseKeyRepository->getModel();
            }

            $this->saveSrResponseKey($responseKey, $key, $srResponseKeySaveData);
        }

        return $this->hasErrors();
    }

    protected function saveSrResponseKeyByName(string $name, string $value): bool
    {
        $sResponseKey = $this->srResponseKeyRepository->findOneSrResponseKeysWithRelation(
            $this->destSr,
            [],
            ['name' => $name]
        );

        if (!$sResponseKey) {
            if (
                !$this->responseKeyRepository->createServiceResponseKey(
                    $this->destService,
                    ['name' => $name]
                )
            ) {
                $this->addError(
                    'error',
                    "Error creating sr response key | serviceResponseKey: {$name} | srResponseKeyValue: {$value}"
                );
                return false;
            }
            $sResponseKey = $this->srResponseKeyRepository->getModel();
        }
        return $this->saveSrResponseKey($sResponseKey, $value);
    }

    protected function saveSrResponseKey(SResponseKey $sResponseKey, string $value, ?array $data = []): bool
    {
        $srResponseKey = $sResponseKey
        ->srResponseKey()
        ->where('sr_id', $this->destSr->id)
        ->first();

        if ($srResponseKey && !empty($srResponseKey->value) && !$this->overwrite) {
            return true;
        }

        $data['value'] = $value;
        $save = $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $this->destSr,
            $sResponseKey,
            $data
        );
        if (!$save) {
            $this->addError(
                'error',
                "Error saving sr response key | serviceResponseKey: {$sResponseKey->name} | srResponseKeyValue: {$value}"
            );
            return false;
        }
        return true;
    }
    public function handleResponse(Sr $sr, ApiResponse $response): bool
    {
        return false;
    }

    public function runSrRequest(Sr $sr, ?array $query = []): ApiResponse
    {
        return new ApiResponse();
    }

}
