<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate;

use Truvoicer\TfDbReadCore\Enums\Property\PropertyType;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Repositories\SrRepository;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types\PopulateTypeJson;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types\PopulateTypeXml;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use Truvoicer\TfDbReadCore\Traits\Error\ErrorTrait;
use Truvoicer\TfDbReadCore\Traits\User\UserTrait;
use Exception;
use Illuminate\Support\Arr;

class PopulateFactory
{
    use UserTrait, PopulateTrait, ErrorTrait;

    private SrRepository $srRepository;

    public function __construct(
        private ProviderService $providerService,
        private SrConfigService $srConfigService,
        private PopulateTypeXml $populateTypeXml,
        private PopulateTypeJson $populateTypeJson
    ) {
        $this->srRepository = new SrRepository();
    }

    public function create(Sr $destSr, array $sourceSrs, ?array $query = [])
    {
        $this->srRepository->addWhere('id', $sourceSrs, 'in');
        $fetchSourceSrs = $this->srRepository->findMany();
        if (is_array($query) && Arr::isList($query)) {
            $query = Arr::mapWithKeys($query, function (array $item, int $key) {
                if (
                    array_key_exists('name', $item) &&
                    array_key_exists('value', $item)
                ) {
                    return [$item['name'] => $item['value']];
                }

                $arrayKeys = array_keys($item);
                $key = $arrayKeys[array_key_first($arrayKeys)];
                return [$key => $item[$key]];
            });
        }
        foreach ($fetchSourceSrs as $sr) {
            $provider = $sr->provider;


            /** @var \App\Models\Provider|null $provider */
            $entityProvider = $this->providerService->getProviderEntityFromProviderProperties(
                $provider
            );

            if ($entityProvider) {
                $responseFormat = $this->providerService
                    ->getProviderPropertyValue(
                        $entityProvider,
                        PropertyType::RESPONSE_FORMAT->value
                    );
            } else {
                $responseFormat = $this->srConfigService->getConfigValue($sr, PropertyType::RESPONSE_FORMAT->value);
            }

            if (!$responseFormat) {
                continue;
            }
            switch ($responseFormat) {
                case ResponseManager::CONTENT_TYPE_JSON:
                    $this->populateTypeJson->setUser($this->getUser());
                    $this->populateTypeJson->setOverwrite($this->overwrite);
                    $this->populateTypeJson->setData($this->data);

                    $this->populateTypeJson->populate($destSr, $sr, $query);
                    if ($this->populateTypeJson->hasErrors()) {
                        $this->setErrors(array_merge($this->getErrors(), $this->populateTypeJson->getErrors()));
                    }
                    break;
                case ResponseManager::CONTENT_TYPE_XML:
                    $this->populateTypeXml->setUser($this->getUser());
                    $this->populateTypeXml->setOverwrite($this->overwrite);
                    $this->populateTypeXml->setData($this->data);

                    $this->populateTypeXml->populate($destSr, $sr, $query);
                    if ($this->populateTypeXml->hasErrors()) {
                        $this->setErrors(array_merge($this->getErrors(), $this->populateTypeXml->getErrors()));
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
