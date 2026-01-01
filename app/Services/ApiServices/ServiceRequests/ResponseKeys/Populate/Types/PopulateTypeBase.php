<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use App\Enums\Ai\AiClient;
use Truvoicer\TruFetcherGet\Models\S;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SResponseKey;
use Truvoicer\TruFetcherGet\Repositories\SResponseKeyRepository;
use Truvoicer\TruFetcherGet\Repositories\SrRepository;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeyRepository;
use Truvoicer\TruFetcherGet\Services\ApiManager\Client\ApiClientHandler;
use Truvoicer\TruFetcherGet\Services\ApiManager\Client\Entity\ApiRequest;
use Truvoicer\TruFetcherGet\Services\ApiManager\Operations\ApiRequestService;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\Entity\ApiDetailedResponse;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\Handlers\ResponseHandler;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\PopulateTrait;
use Truvoicer\TruFetcherGet\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TruFetcherGet\Traits\Error\ErrorTrait;
use Truvoicer\TruFetcherGet\Traits\User\UserTrait;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pusher\ApiErrorException;

class PopulateTypeBase
{
    use UserTrait, ErrorTrait, PopulateTrait;

    protected SrRepository $srRepository;
    protected SrResponseKeyRepository $srResponseKeyRepository;
    private SResponseKeyRepository $responseKeyRepository;

    protected Sr $destSr;
    protected S $destService;
    protected ApiDetailedResponse $response;

    protected array $findItemsArray = [];
    protected array $score = [];

    public function __construct(
        protected ApiRequestService $requestOperation,
        protected SrConfigService   $srConfigService,
        protected ResponseHandler   $responseHandler
    ) {
        $this->srRepository = new SrRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
    }

    public function populate(Sr $destSr, Sr $sourceSr, ?array $query = [])
    {
        $destService = $destSr->s()->first();

        if (!$destService) {
            throw new Exception(
                sprintf(
                    'This sr does not have a service attached. sr: %s (%s)',
                    $destSr->label,
                    $destSr->name
                )
            );
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

    protected function parseAndSaveResponseKey(array $data): void
    {

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


    protected function parseAndSaveResponseKeyWithAi(array $data): void
    {
        if (empty($this->data['ai_clients'])) {
            throw new Exception('ai_clients is missing from request.');
        }
        if (!is_array($this->data['ai_clients'])) {
            throw new Exception('ai_clients is not an array.');
        }
        if (!count($this->data['ai_clients'])) {
            throw new Exception('ai_clients is empty.');
        }
        $apiClient = app(ApiClientHandler::class);
        $apiRequest = app(ApiRequest::class);
        $responseKeys = $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $this->destSr,

        );

        $aiClients = $this->data['ai_clients'];
        $successResponse = false;
        foreach ($aiClients as $aiClient) {
            if ($successResponse) {
                continue;
            }
            $aiClientEnum = AiClient::tryFrom($aiClient);
            if (!$aiClientEnum) {
                throw new Exception('AI client is invalid enum.');
            }
            $apiTypeEnum = $aiClientEnum->apiType();
            $apiRequest->setApiType(
                $apiTypeEnum
            );

            $accessToken = $aiClientEnum->apiKey();
            if ($accessToken) {
                $apiRequest->setAccessToken(
                    $accessToken
                );
            }

            $prompt = $aiClientEnum->populatePrompt(
                $data,
                $responseKeys->pluck('name')->toArray()
            );
            $apiRequest->setAiPrompt(
                $prompt
            );
            try {
                $response = $apiClient->sendRequest($apiRequest);

                $responseManager = app(ResponseManager::class)->setApiType($apiTypeEnum);

                $parseResponse = $responseManager->getJsonBody($response);

                if (!empty($parseResponse['mappings']) && is_array($parseResponse['mappings'])) {
                    foreach ($parseResponse['mappings'] as $apiResponseKey => $sResponseKey) {
                        $this->updateOrCreateResponseKey(
                            $responseKeys,
                            $sResponseKey,
                            $apiResponseKey
                        );
                    }
                }
                if (!empty($parseResponse['new_keys_created']) && is_array($parseResponse['new_keys_created'])) {
                    foreach ($parseResponse['new_keys_created'] as $apiResponseKey => $sResponseKey) {
                        $this->updateOrCreateResponseKey(
                            $responseKeys,
                            $sResponseKey,
                            $apiResponseKey
                        );
                    }
                }

                $successResponse = true;
            } catch (Exception $e) {
                $provider = $this->destSr->provider;
                $this->addError(
                    'populate_response_key_error',
                    sprintf(
                        'Error populating response keys for: provider: [%d] %s (%) | sr [%d] %s (%s) | ai client: %s | message: %s',
                        $provider->id,
                        $provider->label,
                        $provider->name,
                        $this->destSr->id,
                        $this->destSr->label,
                        $this->destSr->name,
                        $aiClientEnum->label(),
                        $e->getMessage()
                    )
                );
                continue;
            }
        }
    }
    private function updateOrCreateResponseKey(Collection $responseKeys, string $sResponseKeyName, string $apiResponseKeyName)
    {

        $srResponseKeySaveData = [
            'list_item' => true,
            'show_in_response' => true
        ];
        $responseKey = $responseKeys->firstWhere('name', $sResponseKeyName);
        if (!$responseKey) {
            $createSResponseKey = $this->responseKeyRepository->createServiceResponseKey(
                $this->destService,
                ['name' => $sResponseKeyName]
            );
            if (!$createSResponseKey) {
                return false;
            }
            $responseKey = $this->responseKeyRepository->getModel();
        }

        $this->saveSrResponseKey($responseKey, $apiResponseKeyName, $srResponseKeySaveData);
    }
    protected function populateResponseKeys(array $data): bool
    {
        if (!Arr::isAssoc($data)) {
            return false;
        }

        if (!empty($this->data['enable_ai'])) {
            $this->parseAndSaveResponseKeyWithAi($data);
        } else {
            $this->parseAndSaveResponseKey($data);
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
    public function handleResponse(Sr $sr, ApiDetailedResponse $response): bool
    {
        return false;
    }

    public function runSrRequest(Sr $sr, ?array $query = []): ApiDetailedResponse
    {
        return new ApiDetailedResponse();
    }
}
