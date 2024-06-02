<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Property;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Repositories\PropertyRepository;
use App\Repositories\SrConfigRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrConfigService extends BaseService
{

    const REQUEST_CONFIG_ITEM_REQUIRED = "required";
    const REQUEST_CONFIG_ITEM_NAME = "name";
    const REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE = "value_type";
    const REQUEST_CONFIG_ITEM_VALUE = "value";
    const REQUEST_CONFIG_ITEM_ARRAY_VALUE = "array_value";
    const REQUEST_CONFIG_ITEM_VALUE_CHOICES = "value_choices";

    private PropertyRepository $propertyRepository;
    private SrConfigRepository $requestConfigRepo;
    private SrService $srService;

    public function __construct(SrService $srService)
    {
        parent::__construct();
        $this->srService = $srService;
        $this->propertyRepository = new PropertyRepository();
        $this->requestConfigRepo = new SrConfigRepository();
    }

    public function findBySr(Sr $serviceRequest) {
        return $this->requestConfigRepo->findBySr($serviceRequest);
    }

    public function findConfigForOperationBySr(Sr $serviceRequest) {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (!$parentServiceRequest instanceof Sr) {
            return $this->findBySr($serviceRequest);
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->config_override)) {
            return $this->findBySr($parentServiceRequest);
        }
        return $this->findBySr($serviceRequest);
    }

    public function findByParams(Sr $serviceRequest) {
        $this->requestConfigRepo->setPagination(true);
        return $this->requestConfigRepo->findByParams(
            $serviceRequest,
            array_map(function ($property) {
                return $property['name'];
            }, DefaultData::getProviderProperties())
        );
    }


    public function requestConfigValidator(Sr $serviceRequest, ?bool $requiredOnly = false) {
        $provider = $serviceRequest->provider()->first();
        $apiAuthTypeProviderProperty = $this->propertyRepository->getProviderPropertyByPropertyName(
            $provider, "api_authentication_type"
        );
        if (!($apiAuthTypeProviderProperty instanceof Property)) {
            throw new BadRequestHttpException("Provider api_authentication_type property not found");
        }
        $config = [];
        switch ($apiAuthTypeProviderProperty->providerProperty->value) {
            case DataConstants::AUTH_BASIC:
                $config = DefaultData::getServiceRequestBasicAuthConfig();
                break;
            case DataConstants::AUTH_BEARER:
                $config = DefaultData::getServiceRequestBearerAuthConfig();
                break;
            case DataConstants::OAUTH2:
                $config = DefaultData::getServiceRequestOauthConfig();
                break;
        }

        $defaultConfig = DefaultData::getServiceRequestConfig();
        $config = array_merge($defaultConfig, $config);
        if ($requiredOnly) {
            $config = array_filter($config, function ($item) {
                return (
                    isset($item[self::REQUEST_CONFIG_ITEM_REQUIRED]) &&
                    $item[self::REQUEST_CONFIG_ITEM_REQUIRED] === true
                );
            });
        }
//        $this->createDefaultRequestConfigs(
//            $serviceRequest,
//            $config
//        );
        return true;
    }

    private function getRequestConfigData(array $data)
    {
        $fields = [
            'value',
            'value_type',
            'array_value',
            'value_choices',
        ];

        $configData = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $configData[$field] = $data[$field];
            }
        }

        if (isset($configData['array_value']) && !is_array($configData['array_value'])) {
            throw new BadRequestHttpException("Array value is invalid");
        }
        if (isset($configData['value_choices']) && !is_array($configData['value_choices'])) {
            throw new BadRequestHttpException("Value choices is invalid.");
        }
        return $data;
    }

    public function saveRequestConfig(Sr $serviceRequest, Property $property, array $data)
    {
        if (empty($data['value_type'])) {
            throw new BadRequestHttpException("Value type is required.");
        }
        return match ($data['value_type']) {
            'text', 'choice' => $this->requestConfigRepo->saveSrConfigProperty($serviceRequest, $property, [
                'value' => $data['value'],
                'array_value' => null
            ]),
            'list' => $this->requestConfigRepo->saveSrConfigProperty($serviceRequest, $property, [
                'array_value' => $data['array_value'],
                'value' => null,
            ]),
            default => throw new BadRequestHttpException("Invalid value type."),
        };
    }

    public function deleteRequestConfig(Sr $serviceRequest, Property $property): bool {
        return ($this->requestConfigRepo->deleteSrConfigProperty(
            $serviceRequest,
            $property
        )) > 0;
    }

    public function getRequestConfigRepo(): SrConfigRepository
    {
        return $this->requestConfigRepo;
    }
    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service request config ids provided.");
        }
        return $this->requestConfigRepo->deleteBatch($ids);
    }
}
