<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Provider;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestConfig;
use App\Models\ServiceRequestParameter;
use App\Models\ServiceResponseKey;
use App\Library\Defaults\DefaultData;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestConfigRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceResponseKeyRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestConfigService extends BaseService
{

    const REQUEST_CONFIG_ITEM_NAME = "item_name";
    const REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE = "selected_value_type";
    const REQUEST_CONFIG_ITEM_VALUE = "item_value";
    const REQUEST_CONFIG_ITEM_ARRAY_VALUE = "item_array_value";
    const REQUEST_CONFIG_ITEM_VALUE_CHOICES = "item_value_choices";
    private $entityManager;
    private $httpRequestService;
    private $serviceRepository;
    private $providerService;
    private $serviceRequestRepository;
    private $requestParametersRepo;
    private $requestConfigRepo;
    private $responseKeysRepo;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                ProviderService $providerService, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->providerService = $providerService;
        $this->httpRequestService = $httpRequestService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->requestConfigRepo = new ServiceRequestConfigRepository();
        $this->responseKeysRepo = new ServiceResponseKeyRepository();
    }

    public function getResponseKeysRequestsConfigList(int $serviceRequestId, int $providerId, string $sort, string $order, int $count) {
        $serviceRequest = $this->serviceRequestRepository->findById($serviceRequestId);
        $provider = $this->providerService->getProviderById($providerId);
        $responseKeys = $this->responseKeysRepo->findBy(["service" => $serviceRequest->getService()]);
        $list = array_map(function ($item) use($provider, $serviceRequest) {
            $listObject = new \stdClass();
            $listObject->key_name = $item->getId();
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

    public function findByParams(int $serviceRequestId, string $sort, string $order, int $count) {
        $serviceRequest = $this->serviceRequestRepository->findById($serviceRequestId);
        if (!$serviceRequest) {
            return false;
        }
        if (!$this->requestConfigValidator($serviceRequest)) {
            return false;
        }
        return $this->requestConfigRepo->findByParams($serviceRequest, $sort, $order, $count);
    }

    public function requestConfigValidator(ServiceRequest $serviceRequest) {
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

    private function getRequestConfigObject(ServiceRequestConfig $requestConfig,
                                                       ServiceRequest $serviceRequest, array $data)
    {
        $requestConfig->setItemName($data['item_name']);
        $requestConfig->setValueType($data['selected_value_type']);
        $requestConfig->setItemValue($data['item_value']);
        if (isset($data['item_array_value']) && is_array($data['item_array_value'])) {
            $requestConfig->setItemArrayValue($data['item_array_value']);
        }
        if (isset($data['item_value_choices']) && is_array($data['item_value_choices'])) {
            $requestConfig->setItemValueChoices($data['item_value_choices']);
        }
        $requestConfig->setServiceRequest($serviceRequest);
        return $requestConfig;
    }

    public function createRequestConfig(ServiceRequest $serviceRequest, array $data)
    {
        $requestConfig = $this->getRequestConfigObject(new ServiceRequestConfig(), $serviceRequest, $data);
        if ($this->httpRequestService->validateData($requestConfig)) {
            return $this->requestConfigRepo->save($requestConfig);
        }
        return false;
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

    public function updateRequestConfig(ServiceRequest $serviceRequest, ServiceRequestConfig $serviceRequestConfig, array $data)
    {
        $requestConfig = $this->getRequestConfigObject($serviceRequestConfig, $serviceRequest, $data);
        if ($this->httpRequestService->validateData($requestConfig)) {
            return $this->requestConfigRepo->save($requestConfig);
        }
        return false;
    }

    public function deleteRequestConfigById(int $id) {
        $requestConfig = $this->requestConfigRepo->find($id);
        if ($requestConfig === null) {
            throw new BadRequestHttpException(sprintf("Service request config item id: %s not found in database.", $id));
        }
        return $this->deleteRequestConfig($requestConfig);
    }

    public function deleteRequestConfig(ServiceRequestConfig $serviceRequestConfig) {
        return $this->requestConfigRepo->delete($serviceRequestConfig);
    }
}
