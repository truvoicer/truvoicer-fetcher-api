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
        protected ResponseHandler  $responseHandler
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
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $keys[] = $key;
                $keys = array_merge($keys, $this->parseDataArrayValue($value, $keys));
                continue;
            }
            $keys[] = $key;
        }
        return $keys;
    }
    protected function parseResponseKey(array $data): array
    {
        $keys = [];
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $keys[] = $key;
                continue;
            }
            if (is_array($value)) {
                if (array_key_exists('xml_value_type', $value) && $value['xml_value_type'] === 'attribute') {
                    if (is_array($value['attributes'])) {
                        $keys[] = sprintf(
                            '%s.attributes.%s',
                            $key,
                            implode('.', $this->parseDataArrayValue($value['attributes']))
                        );
                    }
                    if (is_array($value['values'])) {
                        $keys[] = sprintf(
                            '%s.%s',
                            $key,
                            implode('.', $this->parseDataArrayValue($value['values']))
                        );
                    } else {
                        $keys[] = $key;
                    }
                    continue;
                }
                $keys[] = sprintf(
                    '%s.%s',
                    $key,
                    implode('.', $this->parseDataArrayValue($value))
                );
            }
        }
        return $keys;
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
        dd($parsedData);
        foreach ($parsedData as $key) {

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
        dd($data);
        $this->parseResponseKey($data);

        return $this->hasErrors();
    }

    protected function saveSrResponseKey(string $name, string $value): bool
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

        $srResponseKey = $sResponseKey->srResponseKey()->first();
        if ($srResponseKey && !empty($srResponseKey->value) && !$this->overwrite) {
            return true;
        }
        $save = $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $this->destSr,
            $sResponseKey,
            ['value' => $value]
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
