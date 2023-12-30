<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestConfig;
use App\Library\Defaults\DefaultData;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestConfigRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceResponseKeyRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestConfigService extends BaseService
{

    const REQUEST_CONFIG_ITEM_NAME = "item_name";
    const REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE = "selected_value_type";
    const REQUEST_CONFIG_ITEM_VALUE = "item_value";
    const REQUEST_CONFIG_ITEM_ARRAY_VALUE = "item_array_value";
    const REQUEST_CONFIG_ITEM_VALUE_CHOICES = "item_value_choices";

    private $serviceRepository;
    private $providerService;
    private $serviceRequestRepository;
    private $requestParametersRepo;
    private $requestConfigRepo;
    private $responseKeysRepo;

    public function __construct(ProviderService $providerService)
    {
        parent::__construct();
        $this->providerService = $providerService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->requestConfigRepo = new ServiceRequestConfigRepository();
        $this->responseKeysRepo = new ServiceResponseKeyRepository();
    }

    public function getResponseKeysRequestsConfigList(int $serviceRequestId, int $providerId, string $sort, string $order, int $count) {
        $serviceRequest = $this->serviceRequestRepository->findById($serviceRequestId);
        $provider = $this->providerService->getProviderById($providerId);

        $this->responseKeysRepo->addWhere("service", $serviceRequest->service()->id);
        $responseKeys = $serviceRequest->service()->get()->responseKey()->get()->toArray();

        $list = array_map(function ($item) use($provider, $serviceRequest) {
            $listObject = new \stdClass();
            $listObject->key_name = $item->id;
            $listObject->key_name = $item->getKeyName();
            $listObject->key_value = $item->getKeyValue();
            $listObject->item_value = "";
            $listObject->item_array_value = "";
            $getConfig = $this->requestConfigRepo->getRequestConfigByName($provider, $serviceRequest, $item->getKeyName());
            if ($getConfig !== null) {
                $listObject->item_value = $getConfig->getItemValue();
                $listObject->item_array_value = $getConfig->getItemArrayValue();
                $listObject->item_value_choices = $getConfig->getItemValueChoices();
            }
            return $listObject;
        }, $responseKeys);
    }

    public function findByParams(ServiceRequest $serviceRequest, string $sort, string $order, int $count) {
        return $this->requestConfigRepo->findByParams($serviceRequest, $sort, $order, $count);
    }

    public function requestConfigValidator(Model $serviceRequest) {
        $provider = $serviceRequest->getProvider();
        $apiAuthTypeProviderProperty = $this->providerService->getProviderPropertyObjectByName(
            $provider, "api_authentication_type"
        );
        if (!property_exists($apiAuthTypeProviderProperty, "property_value")) {
            throw new BadRequestHttpException("Provider api_authentication_type property not found");
        }

        switch ($apiAuthTypeProviderProperty->property_value) {
            case ApiBase::AUTH_BASIC:
                $config = DefaultData::getServiceRequestBasicAuthConfig();
                break;
            case ApiBase::AUTH_BEARER:
                $config = DefaultData::getServiceRequestBearerAuthConfig();
                break;
            default:
                throw new BadRequestHttpException("Provider api_authentication_type property not found");
        }

        $defaultConfig = DefaultData::getServiceRequestConfig();
        $this->createDefaultRequestConfigs(
            $serviceRequest,
            array_merge($defaultConfig, $config)
        );
        return true;
    }

    private function getRequestConfigData(array $data)
    {
        $fields = [
            'name',
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

    public function createRequestConfig(ServiceRequest $serviceRequest, array $data)
    {
        return $this->requestConfigRepo->createRequestConfig(
            $serviceRequest,
            $this->getRequestConfigData($data)
        );
    }

    public function createDefaultRequestConfigs(ServiceRequest $serviceRequest, array $defaultConfig = []) {
    $provider = $serviceRequest->getProvider();
        foreach ($defaultConfig as $item) {
            $findConfig = $this->requestConfigRepo->getRequestConfigByName(
                $provider, $serviceRequest,
                $item[self::REQUEST_CONFIG_ITEM_NAME]
            );
            if ($findConfig instanceof ServiceRequestConfig) {
                continue;
            }
            $insertData = [
                self::REQUEST_CONFIG_ITEM_NAME => $item[self::REQUEST_CONFIG_ITEM_NAME],
                self::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => $item[self::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE],
                self::REQUEST_CONFIG_ITEM_VALUE => $item[self::REQUEST_CONFIG_ITEM_VALUE],
                self::REQUEST_CONFIG_ITEM_ARRAY_VALUE => $item[self::REQUEST_CONFIG_ITEM_ARRAY_VALUE],
            ];
            if (isset($item[self::REQUEST_CONFIG_ITEM_VALUE_CHOICES])) {
                $insertData[self::REQUEST_CONFIG_ITEM_VALUE_CHOICES] = $item[self::REQUEST_CONFIG_ITEM_VALUE_CHOICES];
            }
           $create = $this->createRequestConfig($serviceRequest, $insertData);
           if (!$create) {
               throw new BadRequestHttpException(sprintf(
                   "Service request config item: %s not created.",
                   $item[self::REQUEST_CONFIG_ITEM_NAME])
               );
           }
        }
    }

    public function updateRequestConfig(ServiceRequestConfig $serviceRequestConfig, array $data)
    {
        $this->requestConfigRepo->setModel($serviceRequestConfig);
        return $this->requestConfigRepo->save($this->getRequestConfigData($data));
    }

    public function deleteRequestConfig(ServiceRequestConfig $serviceRequestConfig) {
        $this->requestConfigRepo->setModel($serviceRequestConfig);
        return $this->requestConfigRepo->delete();
    }

    public function getRequestConfigRepo(): ServiceRequestConfigRepository
    {
        return $this->requestConfigRepo;
    }
}
