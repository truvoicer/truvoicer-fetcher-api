<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Property\PropertyService;
use App\Services\Tools\IExport\IExportTypeService;

class ProviderImporterService extends ProviderService
{

    public function __construct(
        PropertyService $propertyService,
        CategoryService $categoryService,
        ApiService $apiService,
        ResponseKeysService $responseKeysService,
        AccessControlService $accessControlService
    ) {
        parent::__construct(
            $propertyService,
            $categoryService,
            $apiService,
            $responseKeysService,
            $accessControlService
        );
    }

    private function buildServiceRequests(Provider $provider, array $mappings)
    {
        foreach ($provider->serviceRequest()->get() as $sourceServiceRequest) {
            $destService = $this->apiService->getServiceById(
                IExportTypeService::getImportMappingValue(
                    $provider->name,
                    "service",
                    "service_request",
                    $sourceServiceRequest->name,
                    $mappings
                )
            );
            $destServiceRequestCategory = $this->categoryService->getCategoryById(
                IExportTypeService::getImportMappingValue(
                    $provider->name,
                    "category",
                    "service_request",
                    $sourceServiceRequest->name,
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

    private function buildServiceRequestParameters(Sr $sourceServiceRequest)
    {
        foreach ($sourceServiceRequest->srParameter()->get() as $requestParameter) {
            if ($requestParameter->getParameterName() === null) {
                $sourceServiceRequest->removeServiceRequestParameter($requestParameter);
            }
        }
        return $sourceServiceRequest;
    }

    private function buildServiceRequestConfigs(Sr $sourceServiceRequest)
    {
        foreach ($sourceServiceRequest->srConfig()->get() as $requestConfig) {
            if ($requestConfig->getItemName() === null) {
                $sourceServiceRequest->removeServiceRequestConfig($requestConfig);
            }
        }
        return $sourceServiceRequest;
    }

    private function buildServiceRequestResponseKeys(Sr $sourceServiceRequest, S $destService)
    {
        foreach ($sourceServiceRequest->srResponseKey()->get() as $responseKey) {
            if ($responseKey->getServiceResponseKey() === null) {
                $sourceServiceRequest->removeServiceRequestResponseKey($responseKey);
                continue;
            }
            if ($responseKey->value === null) {
                $sourceServiceRequest->removeServiceRequestResponseKey($responseKey);
                continue;
            }
            $getResponseKeyByName = $this->responseKeysService->getServiceResponseKeyByName(
                $destService,
                $responseKey->getServiceResponseKey()->getKeyName()
            );
            if ($getResponseKeyByName === null) {
                $createResponseKey = $this->responseKeysService->createServiceResponseKeys([
                    "service_id" => $destService->id,
                    "key_name" => $responseKey->getServiceResponseKey()->getKeyName(),
                    "key_value" => $responseKey->getServiceResponseKey()->getKeyValue()
                ]);
                if ($createResponseKey instanceof SResponseKey) {
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

    private function buildProviderProperties(Provider $provider, array $mappings)
    {
        foreach ($provider->property()->get() as $providerProperty) {
            $property = $this->propertyService->getPropertyById(
                IExportTypeService::getImportMappingValue(
                    $provider->name,
                    "property",
                    "provider_property",
                    $providerProperty->getProperty()->name,
                    $mappings
                )
            );
            $providerProperty->setProperty($property);
        }
        return $provider;
    }

    private function buildProviderCategories(Provider $provider, array $mappings)
    {
        $destProviderCategory = $this->categoryService->getCategoryById(
            IExportTypeService::getImportMappingValue(
                $provider->name,
                "category",
                "provider",
                $provider->name,
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
                    "name" => $provider->name,
                    "label" => $provider->label
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
            foreach ($provider->serviceRequest()->get() as $serviceRequest) {
                $mappings["data"]["service"]["sources"]["service_request"][] = [
                    "name" => $serviceRequest->name,
                    "label" => $serviceRequest->getServiceRequestLabel(),
                ];
                $mappings["data"]["category"]["sources"]["service_request"][] = [
                    "name" => $serviceRequest->name,
                    "label" => $serviceRequest->getServiceRequestLabel(),
                ];
            }
            foreach ($provider->property()->get() as $providerProperty) {
                $mappings["data"]["property"]["sources"]["provider_property"][] = [
                    "provider_property_value" => $providerProperty->value,
                    "property_name" => $providerProperty->getProperty()->name,
                ];
            }
            $mappings["data"]["category"]["sources"]["provider"][] = [
                "name" => $provider->name,
                "label" => $provider->label,
            ];
            return $mappings;
        }, $data);
    }
}
