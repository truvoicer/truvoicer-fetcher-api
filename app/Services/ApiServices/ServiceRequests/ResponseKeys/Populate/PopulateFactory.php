<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate;

use App\Models\Sr;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types\PopulateTypeJson;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types\PopulateTypeXml;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;

class PopulateFactory
{
    use UserTrait, PopulateTrait, ErrorTrait;

    private SrRepository $srRepository;

    public function __construct(
        private SrConfigService $srConfigService,
        private PopulateTypeXml $populateTypeXml,
        private PopulateTypeJson $populateTypeJson
    )
    {
        $this->srRepository = new SrRepository();
    }

    public function create(Sr $destSr, array $sourceSrs, ?array $query = [])
    {
        $this->srRepository->addWhere('id', $sourceSrs, 'in');
        $fetchSourceSrs = $this->srRepository->findMany();
        if (Arr::isList($query)) {
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
            $responseFormat = $this->srConfigService->getConfigValue($sr, DataConstants::RESPONSE_FORMAT);

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
