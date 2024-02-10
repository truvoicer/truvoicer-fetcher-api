<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Property;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Library\Defaults\DefaultData;
use App\Repositories\PropertyRepository;
use App\Repositories\SRepository;
use App\Repositories\SrConfigRepository;
use App\Repositories\SrParameterRepository;
use App\Repositories\SrRepository;
use App\Repositories\SResponseKeyRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrConfigService extends BaseService
{

    const REQUEST_CONFIG_ITEM_REQUIRED = "required";
    const REQUEST_CONFIG_ITEM_NAME = "name";
    const REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE = "value_type";
    const REQUEST_CONFIG_ITEM_VALUE = "value";
    const REQUEST_CONFIG_ITEM_ARRAY_VALUE = "array_value";
    const REQUEST_CONFIG_ITEM_VALUE_CHOICES = "value_choices";

    private SRepository $serviceRepository;
    private PropertyRepository $propertyRepository;
    private ProviderService $providerService;
    private SrRepository $serviceRequestRepository;
    private SrParameterRepository $requestParametersRepo;
    private SrConfigRepository $requestConfigRepo;
    private SResponseKeyRepository $responseKeysRepo;

    public function __construct(ProviderService $providerService)
    {
        parent::__construct();
        $this->providerService = $providerService;
        $this->propertyRepository = new PropertyRepository();
        $this->serviceRepository = new SRepository();
        $this->serviceRequestRepository = new SrRepository();
        $this->requestParametersRepo = new SrParameterRepository();
        $this->requestConfigRepo = new SrConfigRepository();
        $this->responseKeysRepo = new SResponseKeyRepository();
    }

    public function findBySr(Sr $serviceRequest) {
        return $this->requestConfigRepo->findBySr($serviceRequest);
    }
    public function findByParams(Sr $serviceRequest, string $sort, string $order, ?int $count = null) {
        return $this->requestConfigRepo->findByParams($serviceRequest, $sort, $order, $count);
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
            case ApiBase::AUTH_BASIC:
                $config = DefaultData::getServiceRequestBasicAuthConfig();
                break;
            case ApiBase::AUTH_BEARER:
                $config = DefaultData::getServiceRequestBearerAuthConfig();
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
        $this->createDefaultRequestConfigs(
            $serviceRequest,
            $config
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

    public function createRequestConfig(Sr $serviceRequest, array $data)
    {
        return $this->requestConfigRepo->createRequestConfig(
            $serviceRequest,
            $this->getRequestConfigData($data)
        );
    }

    public function createDefaultRequestConfigs(Sr $serviceRequest, array $defaultConfig = []) {
        $provider = $serviceRequest->provider()->first();

        foreach ($defaultConfig as $item) {
            $findConfig = $this->requestConfigRepo->getRequestConfigByName(
                $serviceRequest,
                $item[self::REQUEST_CONFIG_ITEM_NAME]
            );
            if ($findConfig instanceof SrConfig) {
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

    public function updateRequestConfig(SrConfig $serviceRequestConfig, array $data)
    {
        $this->requestConfigRepo->setModel($serviceRequestConfig);
        return $this->requestConfigRepo->save($this->getRequestConfigData($data));
    }

    public function deleteRequestConfig(SrConfig $serviceRequestConfig) {
        $this->requestConfigRepo->setModel($serviceRequestConfig);
        return $this->requestConfigRepo->delete();
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
