<?php
namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Property\PropertyService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\IExport\IExportTypeService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProviderImporterService extends ProviderService
{

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                PropertyService $propertyService, CategoryService $categoryService,
                                ApiService $apiService, ResponseKeysService $responseKeysService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService) {
        parent::__construct($entityManager, $httpRequestService, $propertyService, $categoryService, $apiService,
            $responseKeysService, $tokenStorage, $accessControlService
        );
    }

    private function buildServiceRequests(Provider $provider, array $mappings) {
        foreach ($provider->getServiceRequests() as $sourceServiceRequest) {
            $destService = $this->apiService->getServiceById(
                IExportTypeService::getImportMappingValue(
                    $provider->getProviderName(),
                    "service",
                    "service_request",
                    $sourceServiceRequest->getServiceRequestName(),
                    $mappings
                )
            );
            $destServiceRequestCategory = $this->categoryService->getCategoryById(
                IExportTypeService::getImportMappingValue(
                    $provider->getProviderName(),
                    "category",
                    "service_request",
                    $sourceServiceRequest->getServiceRequestName(),
                    $mappings
                )
            );
            $sourceServiceRequest->setService($destService);
            $sourceServiceRequest->setCategory($destServiceRequestCategory);
            $sourceServiceRequest = $this->buildServiceRequestResponseKeys($sourceServiceRequest, $destService);
            $sourceServiceRequest = $this->buildServiceRequestParameters($sourceServiceRequest);
            $sourceServiceRequest = $this->buildServiceRequestConfigs($sourceServiceRequest);
        }
        return $provider;
    }

    private function buildServiceRequestParameters(ServiceRequest $sourceServiceRequest)
    {
        foreach ($sourceServiceRequest->getServiceRequestParameters() as $requestParameter) {
            if ($requestParameter->getParameterName() === null) {
                $sourceServiceRequest->removeServiceRequestParameter($requestParameter);
            }
        }
        return $sourceServiceRequest;
    }

    private function buildServiceRequestConfigs(ServiceRequest $sourceServiceRequest)
    {
        foreach ($sourceServiceRequest->getServiceRequestConfigs() as $requestConfig) {
            if ($requestConfig->getItemName() === null) {
                $sourceServiceRequest->removeServiceRequestConfig($requestConfig);
            }
        }
        return $sourceServiceRequest;
    }

    private function buildServiceRequestResponseKeys(ServiceRequest $sourceServiceRequest, Service $destService) {
            foreach ($sourceServiceRequest->getServiceRequestResponseKeys() as $responseKey) {
                if ($responseKey->getServiceResponseKey() === null) {
                    $sourceServiceRequest->removeServiceRequestResponseKey($responseKey);
                    continue;
                }
                if ($responseKey->getResponseKeyValue() === null) {
                    $sourceServiceRequest->removeServiceRequestResponseKey($responseKey);
                    continue;
                }
                $getResponseKeyByName = $this->responseKeysService->getServiceResponseKeyByName(
                    $destService,
                    $responseKey->getServiceResponseKey()->getKeyName()
                );
                if ($getResponseKeyByName === null) {
                    $createResponseKey = $this->responseKeysService->createServiceResponseKeys([
                        "service_id" => $destService->getId(),
                        "key_name" => $responseKey->getServiceResponseKey()->getKeyName(),
                        "key_value" => $responseKey->getServiceResponseKey()->getKeyValue()
                    ]);
                    if ($createResponseKey instanceof ServiceResponseKey) {
                        $responseKey->setServiceResponseKey($createResponseKey);
                    } else {
                        $responseKey->setServiceResponseKey(null);
                        continue;
                    }
                } else {
                    $responseKey->setServiceResponseKey($getResponseKeyByName);
                }
            }

        return $sourceServiceRequest;
    }

    private function buildProviderProperties(Provider $provider, array $mappings) {
        foreach ($provider->getProviderProperties() as $providerProperty) {
            $property = $this->propertyService->getPropertyById(
                IExportTypeService::getImportMappingValue(
                    $provider->getProviderName(),
                    "property",
                    "provider_property",
                    $providerProperty->getProperty()->getPropertyName(),
                    $mappings
                )
            );
            $providerProperty->setProperty($property);
        }
        return $provider;
    }

    private function buildProviderCategories(Provider $provider, array $mappings) {
        $destProviderCategory = $this->categoryService->getCategoryById(
            IExportTypeService::getImportMappingValue(
                $provider->getProviderName(),
                "category",
                "provider",
                $provider->getProviderName(),
                $mappings
            )
        );
        if ($destProviderCategory !== null) {
            $provider->addCategory($destProviderCategory);
        }
        return $provider;
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (Provider $provider) use ($mappings) {
            $provider = $this->buildServiceRequests($provider, $mappings);

            $provider = $this->buildProviderProperties($provider, $mappings);

            $provider = $this->buildProviderCategories($provider, $mappings);

            return $this->providerRepository->createProvider($this->user, $provider);
        }, $data);
    }

    public function getImportMappings($data)
    {
        return array_map(function (Provider $provider) {
            $mappings = [
                "import_entity" => [
                    "name" => $provider->getProviderName(),
                    "label" => $provider->getProviderLabel()
                ],
                "data" => [
                    "service" => [
                        "name" => "service",
                        "label" => "Service"
                    ],
                    "property" => [
                        "name" => "property",
                        "label" => "Property"
                    ],
                    "category" => [
                        "name" => "category",
                        "label" => "Category"
                    ]
                ]
            ];
            $mappings["data"]["service"]["available"] = $this->apiService->getAllServicesArray();
            $mappings["data"]["property"]["available"] = $this->propertyService->getAllPropertiesArray();
            $mappings["data"]["category"]["available"] = $this->categoryService->getAllCategoriesArray();
            foreach ($provider->getServiceRequests() as $serviceRequest) {
                $mappings["data"]["service"]["sources"]["service_request"][] = [
                    "service_request_name" => $serviceRequest->getServiceRequestName(),
                    "service_request_label" => $serviceRequest->getServiceRequestLabel(),
                ];
                $mappings["data"]["category"]["sources"]["service_request"][] = [
                    "service_request_name" => $serviceRequest->getServiceRequestName(),
                    "service_request_label" => $serviceRequest->getServiceRequestLabel(),
                ];
            }
            foreach ($provider->getProviderProperties() as $providerProperty) {
                $mappings["data"]["property"]["sources"]["provider_property"][] = [
                    "provider_property_value" => $providerProperty->getPropertyValue(),
                    "property_name" => $providerProperty->getProperty()->getPropertyName(),
                ];
            }
            $mappings["data"]["category"]["sources"]["provider"][] = [
                "provider_name" => $provider->getProviderName(),
                "provider_label" => $provider->getProviderLabel(),
            ];
            return $mappings;
        }, $data);
    }
}
