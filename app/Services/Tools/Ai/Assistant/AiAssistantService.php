<?php

namespace App\Services\Tools\Ai\Assistant;

use App\Enums\Ai\AiClient;
use App\Models\AiImportConfig;
use App\Repositories\AiImportConfigRepository;
use App\Services\Tools\Ai\Assistant\Prompt\DeepSeek\AssistantDeepSeekPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\Gemini\AssistantGeminiPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\Grok\AssistantGrokPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\OpenAi\AssistantOpenAiPrompt;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Truvoicer\TfDbReadCore\Models\Category;
use Truvoicer\TfDbReadCore\Models\Property;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrParametersService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\Category\CategoryService;
use Truvoicer\TfDbReadCore\Services\Property\PropertyService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use Truvoicer\TfDbReadCore\Traits\User\UserTrait;

class AiAssistantService
{
    use UserTrait;

    public function __construct(
        private ApiService $sService,
        private PropertyService $propertyService,
        private CategoryService $categoryService,
        private ProviderService $providerService,
        private SrService $srService,
        private SrConfigService $srConfigService,
        private SrParametersService $srParametersService,
        private AiImportConfigRepository $aiImportConfigRepository,
    ) {}

    public function build(string $prompt, ?AiClient $aiClient = AiClient::GEMINI)
    {


        // [
        //     "label" => "Eventbrite Events API"
        //     "description" => "Fetches event data from Eventbrite. Uses API Key authentication."
        //     "service" => array:2 [
        //         "name" => "eventbrite_events"
        //         "label" => "Eventbrite Events"
        //     ]
        //     "category" => array:2 [
        //         "name" => "business_events"
        //         "label" => "Business Events"
        //     ]
        //     "provider" => array:4 [
        //         "name" => "eventbrite"
        //         "label" => "Eventbrite"
        //         "global" => false
        //         "categories" => array:1 [
        //         0 => array:2 [
        //             "name" => "business_events"
        //             "label" => "Business Events"
        //         ]
        //         ]
        //     ]
        //     "providerProperties" => array:3 [
        //         0 => array:2 [
        //         "property" => "base_url"
        //         "value" => "https://www.eventbriteapi.com/v3"
        //         ]
        //         1 => array:2 [
        //         "property" => "api_auth_type"
        //         "value" => "auth_bearer"
        //         ]
        //         2 => array:2 [
        //         "property" => "bearer_token"
        //         "value" => "{api_key}"
        //         ]
        //     ]
        //     "serviceRequest" => array:2 [
        //         0 => array:6 [
        //         "default_sr" => true
        //         "name" => "search_events"
        //         "label" => "Search Events"
        //         "service" => array:2 [
        //             "name" => "eventbrite_events"
        //             "label" => "Eventbrite Events"
        //         ]
        //         "category" => array:1 [
        //             0 => array:2 [
        //             "name" => "business_events"
        //             "label" => "Business Events"
        //             ]
        //         ]
        //         "query_parameters" => array:7 [
        //             0 => "q"
        //             1 => "location.address"
        //             2 => "location.within"
        //             3 => "start_date.range_start"
        //             4 => "start_date.range_end"
        //             5 => "sort_by"
        //             6 => "page"
        //         ]
        //         ]
        //         1 => array:6 [
        //         "default_sr" => false
        //         "name" => "get_event"
        //         "label" => "Get Event Details"
        //         "service" => array:2 [
        //             "name" => "eventbrite_events"
        //             "label" => "Eventbrite Events"
        //         ]
        //         "category" => array:1 [
        //             0 => array:2 [
        //             "name" => "business_events"
        //             "label" => "Business Events"
        //             ]
        //         ]
        //         "query_parameters" => []
        //         ]
        //     ]
        //     "serviceRequestConfigProperties" => array:2 [
        //         0 => array:2 [
        //         "service_request_name" => "search_events"
        //         "properties" => array:3 [
        //             0 => array:2 [
        //             "property_name" => "endpoint"
        //             "value" => "/events/search/"
        //             ]
        //             1 => array:2 [
        //             "property_name" => "method"
        //             "value" => "get"
        //             ]
        //             2 => array:2 [
        //             "property_name" => "headers"
        //             "array_value" => array:1 [
        //                 "Accept" => "application/json"
        //             ]
        //             ]
        //         ]
        //         ]
        //         1 => array:2 [
        //         "service_request_name" => "get_event"
        //         "properties" => array:3 [
        //             0 => array:2 [
        //             "property_name" => "endpoint"
        //             "value" => "/events/{event_id}/"
        //             ]
        //             1 => array:2 [
        //             "property_name" => "method"
        //             "value" => "get"
        //             ]
        //             2 => array:2 [
        //             "property_name" => "headers"
        //             "array_value" => array:1 [
        //                 "Accept" => "application/json"
        //             ]
        //             ]
        //         ]
        //         ]
        //     ]
        //     "serviceRequestParameters" => array:10 [
        //         0 => array:3 [
        //         "name" => "q"
        //         "value" => "{query}"
        //         "service_request_name" => "search_events"
        //         ]
        //         1 => array:3 [
        //         "name" => "location.address"
        //         "value" => "{location}"
        //         "service_request_name" => "search_events"
        //         ]
        //         2 => array:3 [
        //         "name" => "location.within"
        //         "value" => "{distance}mi"
        //         "service_request_name" => "search_events"
        //         ]
        //         3 => array:3 [
        //         "name" => "start_date.range_start"
        //         "value" => "{start_date}"
        //         "service_request_name" => "search_events"
        //         ]
        //         4 => array:3 [
        //         "name" => "start_date.range_end"
        //         "value" => "{end_date}"
        //         "service_request_name" => "search_events"
        //         ]
        //         5 => array:3 [
        //         "name" => "sort_by"
        //         "value" => "date"
        //         "service_request_name" => "search_events"
        //         ]
        //         6 => array:3 [
        //         "name" => "page"
        //         "value" => "{page}"
        //         "service_request_name" => "search_events"
        //         ]
        //         7 => array:3 [
        //         "name" => "event_id"
        //         "value" => "{event_id}"
        //         "service_request_name" => "get_event"
        //         ]
        //         8 => array:3 [
        //         "name" => "api_key"
        //         "value" => "{api_key}"
        //         "service_request_name" => "search_events"
        //         ]
        //         9 => array:3 [
        //         "name" => "api_key"
        //         "value" => "{api_key}"
        //         "service_request_name" => "get_event"
        //         ]
        //     ]
        //   ]


        switch ($aiClient) {
            case AiClient::DEEP_SEEK:
                $response = app(AssistantDeepSeekPrompt::class)->makeRequest($prompt);
                break;
            case AiClient::GEMINI:
                $response = app(AssistantGeminiPrompt::class)->makeRequest($prompt);
                break;
            case AiClient::GROK:
                $response = app(AssistantGrokPrompt::class)->makeRequest($prompt);
                break;
            case AiClient::OPEN_AI:
                $response = app(AssistantOpenAiPrompt::class)->makeRequest($prompt);
                break;

            default:
                throw new RuntimeException('Ai client is not supported. | ai client: ' . $aiClient->value);
        }
        if (!$response) {
            throw new RuntimeException('Error making ai request.');
        }

        return $this->createAiImportConfig($response);
    }

    public function createAiImportConfig(array $data): null|AiImportConfig
    {

        $label = (!empty($data['label'])) ? $data['label'] : null;

        $description = (!empty($data['description'])) ? $data['description'] : null;

        if (!$this->aiImportConfigRepository->createImportConfig($this->user, [
            'label' => $label,
            'description' => $description,
            'config' => $data
        ])) {
            throw new RuntimeException(
                'Error storing ai import config. | json: (' . json_encode($data) . ')'
                );
        }
        return $this->aiImportConfigRepository->getModel();
    }


    public function makeImport(AiImportConfig $aiImportConfig)
    {
        $data = $aiImportConfig->config;

        $service = (!empty($data['service']) && is_array($data['service'])) ? $data['service'] : null;
        $serviceData = $this->handleService($service);
        if (!$serviceData) {
            throw new RuntimeException('Error importing service.');
        }

        $category = (!empty($data['category']) && is_array($data['category'])) ? $data['category'] : null;
        $categoryData = $this->handleCategory($category);
        if (!$categoryData) {
            throw new RuntimeException('Error importing category.');
        }

        $provider = (!empty($data['provider']) && is_array($data['provider'])) ? $data['provider'] : null;

        $providerProperties = (!empty($data['providerProperties']) && is_array($data['providerProperties'])) ? $data['providerProperties'] : [];

        $providerData = $this->handleProvider($provider, $providerProperties);
        if (!$providerData) {
            throw new RuntimeException('Error importing provider.');
        }


        $serviceRequest = (!empty($data['serviceRequest']) && is_array($data['serviceRequest'])) ? $data['serviceRequest'] : [];

        $serviceRequestConfigProperties = (!empty($data['serviceRequestConfigProperties']) && is_array($data['serviceRequestConfigProperties'])) ? $data['serviceRequestConfigProperties'] : [];

        $serviceRequestParameters = (!empty($data['serviceRequestParameters']) && is_array($data['serviceRequestParameters'])) ? $data['serviceRequestParameters'] : [];

        $serviceRequestData = $this->handleServiceRequest($providerData, $serviceRequest, $serviceRequestConfigProperties, $serviceRequestParameters);
        if (!$serviceRequestData) {
            throw new RuntimeException('Error importing serviceRequest.');
        }
    }

    private function handleService(array $data): S|null
    {
        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in service data. | json: (' . json_encode($data) . ')'
            );
        }
        if (empty($data['name'])) {
            $data['name'] = Str::slug($data['label']);
        }

        if (!$this->sService->createService($this->user, $data)) {
            throw new Exception(
                'Error storing service. | json: (' . json_encode($data) . ')'
            );
        }
        return $this->sService->getServiceRepository()->getModel();
    }
    private function handleCategory(array $data): Category|null
    {
        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in service data. | json: (' . json_encode($data) . ')'
            );
        }
        if (empty($data['name'])) {
            $data['name'] = Str::slug($data['label']);
        }

        if (!$this->categoryService->createCategory($this->user, $data)) {
            throw new Exception(
                'Error storing category. | json: (' . json_encode($data) . ')'
            );
        }
        return $this->categoryService->getCategoryRepository()->getModel();
    }

    private function prepareProviderPropertySaveData(array $data)
    {
        if (empty($data['value']) && empty($data['array_value']) && empty($data['big_text_value'])) {
            throw new Exception(
                'Provider property has an invalid value. | json: (' . json_encode($data) . ')'
            );
        }
        if (!empty($data['value'])) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT;
            $data['value'] = (string)$data['value'];
        } elseif (!empty($data['array_value']) && is_array($data['array_value'])) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST;
        } elseif (!empty($data['array_value']) && is_array($data['array_value'])) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT;
            $data['big_text_value'] = (string)$data['big_text_value'];
        }
        return $data;
    }

    private function handleProvider(array $data, array $providerProperties): Provider|null
    {

        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in service data. | json: (' . json_encode($data) . ')'
            );
        }
        if (empty($data['name'])) {
            $data['name'] = Str::slug($data['label']);
        }

        if (!$this->providerService->createProvider($this->user, $data)) {
            throw new Exception(
                'Error storing category. | json: (' . json_encode($data) . ')'
            );
        }

        $provider = $this->providerService->getProviderRepository()->getModel();

        foreach ($providerProperties as $providerProperty) {
            if (!empty($providerProperty['property'])) {
                $propertyName = $providerProperty['property'];
            } elseif (!empty($providerProperty['property_name'])) {
                $propertyName = $providerProperty['property_name'];
            } else {
                throw new Exception(
                    'Provider property has no name. | json: (' . json_encode($providerProperty) . ')'
                );
            }
            $findProperty = $this->propertyService->getPropertyByName($propertyName);
            if (!$findProperty) {
                throw new Exception(
                    'Provider property with this name does not exist. | json: (' . json_encode($providerProperty) . ')'
                );
            }
            $create = $this->providerService->createProviderProperty(
                $this->user,
                $provider,
                $findProperty,
                $this->prepareProviderPropertySaveData(
                    $providerProperty
                )
            );
            if (!$create) {
                throw new Exception(
                    'Error storing provider property. | json: (' . json_encode($providerProperty) . ')'
                );
            }
        }

        return $provider;
    }

    //     "serviceRequest" => array:2 [
    //         0 => array:6 [
    //         "default_sr" => true
    //         "name" => "search_events"
    //         "label" => "Search Events"
    //         "service" => array:2 [
    //             "name" => "eventbrite_events"
    //             "label" => "Eventbrite Events"
    //         ]
    //         "category" => array:1 [
    //             0 => array:2 [
    //             "name" => "business_events"
    //             "label" => "Business Events"
    //             ]
    //         ]
    //         "query_parameters" => array:7 [
    //             0 => "q"
    //             1 => "location.address"
    //             2 => "location.within"
    //             3 => "start_date.range_start"
    //             4 => "start_date.range_end"
    //             5 => "sort_by"
    //             6 => "page"
    //         ]
    //         ]
    //         1 => array:6 [
    //         "default_sr" => false
    //         "name" => "get_event"
    //         "label" => "Get Event Details"
    //         "service" => array:2 [
    //             "name" => "eventbrite_events"
    //             "label" => "Eventbrite Events"
    //         ]
    //         "category" => array:1 [
    //             0 => array:2 [
    //             "name" => "business_events"
    //             "label" => "Business Events"
    //             ]
    //         ]
    //         "query_parameters" => []
    //         ]
    //     ]
    //     "serviceRequestConfigProperties" => array:2 [
    //         0 => array:2 [
    //         "service_request_name" => "search_events"
    //         "properties" => array:3 [
    //             0 => array:2 [
    //             "property_name" => "endpoint"
    //             "value" => "/events/search/"
    //             ]
    //             1 => array:2 [
    //             "property_name" => "method"
    //             "value" => "get"
    //             ]
    //             2 => array:2 [
    //             "property_name" => "headers"
    //             "array_value" => array:1 [
    //                 "Accept" => "application/json"
    //             ]
    //             ]
    //         ]
    //         ]
    //         1 => array:2 [
    //         "service_request_name" => "get_event"
    //         "properties" => array:3 [
    //             0 => array:2 [
    //             "property_name" => "endpoint"
    //             "value" => "/events/{event_id}/"
    //             ]
    //             1 => array:2 [
    //             "property_name" => "method"
    //             "value" => "get"
    //             ]
    //             2 => array:2 [
    //             "property_name" => "headers"
    //             "array_value" => array:1 [
    //                 "Accept" => "application/json"
    //             ]
    //             ]
    //         ]
    //         ]
    //     ]
    //     "serviceRequestParameters" => array:10 [
    //         0 => array:3 [
    //         "name" => "q"
    //         "value" => "{query}"
    //         "service_request_name" => "search_events"
    //         ]
    //         1 => array:3 [
    //         "name" => "location.address"
    //         "value" => "{location}"
    //         "service_request_name" => "search_events"
    //         ]
    //         2 => array:3 [
    //         "name" => "location.within"
    //         "value" => "{distance}mi"
    //         "service_request_name" => "search_events"
    //         ]
    //         3 => array:3 [
    //         "name" => "start_date.range_start"
    //         "value" => "{start_date}"
    //         "service_request_name" => "search_events"
    //         ]
    //     ]
    private function handleServiceRequest(Provider $provider, array $srs, array $serviceRequestConfigProperties, array $serviceRequestParameters): Collection
    {
        $srCache = collect();
        foreach ($srs as $srData) {

            if (empty($srData['label'])) {
                throw new Exception(
                    'Label (label) not found in service data. | json: (' . json_encode($srData) . ')'
                );
            }
            if (empty($srData['name'])) {
                $srData['name'] = Str::slug($srData['label']);
            }

            if (!$this->srService->createServiceRequest($provider, $srData)) {
                throw new Exception(
                    'Error storing category. | json: (' . json_encode($srData) . ')'
                );
            }

            $srCache->add($this->srService->getServiceRequestRepository()->getModel());
        }


        foreach ($serviceRequestConfigProperties as $srConfigProperty) {
            if (!empty($srConfigProperty['service_request_name'])) {
                $srName = $srConfigProperty['service_request_name'];
            } else {
                throw new Exception(
                    'Sr config property has no name. | json: (' . json_encode($srConfigProperty) . ')'
                );
            }
            $findSr = $srCache->where('name', $srName);
            if (!$findSr) {
                throw new Exception(
                    'Sr with this name does not exist. | name:' . $srName
                );
            }

            $properties = (!empty($srConfigProperty['properties']) && is_array($srConfigProperty['properties'])) ? $srConfigProperty['properties'] : [];

            foreach ($properties as $propertyData) {
                if (!empty($propertyData['property'])) {
                    $propertyName = $propertyData['property'];
                } elseif (!empty($propertyData['property_name'])) {
                    $propertyName = $propertyData['property_name'];
                } else {
                    throw new Exception(
                        'Provider property has no name. | json: (' . json_encode($propertyData) . ')'
                    );
                }
                $findProperty = $this->propertyService->getPropertyByName($propertyName);
                if (!$findProperty) {
                    throw new Exception(
                        'Provider property with this name does not exist. | json: (' . json_encode($propertyData) . ')'
                    );
                }
                $create = $this->srConfigService->saveRequestConfig(
                    $this->user,
                    $sr,
                    $findProperty,
                    $this->prepareProviderPropertySaveData(
                        $propertyData
                    )
                );
                if (!$create) {
                    throw new Exception(
                        'Error storing provider property. | json: (' . json_encode($propertyData) . ')'
                    );
                }
            }
        }

        foreach ($serviceRequestParameters as $sRParameter) {
            if (!empty($sRParameter['service_request_name'])) {
                $srName = $sRParameter['service_request_name'];
            } else {
                throw new Exception(
                    'Sr parameter has no service_request_name. | json: (' . json_encode($sRParameter) . ')'
                );
            }
            $findSr = $srCache->where('name', $srName);
            if (!$findSr instanceof Sr) {
                throw new Exception(
                    'Sr with this name does not exist. | name:' . $srName
                );
            }
            $create = $this->srParametersService->createRequestParameter(
                $findSr,
                $sRParameter
            );
            if (!$create) {
                throw new Exception(
                    'Error storing sr parameter. | json: (' . json_encode($sRParameter) . ')'
                );
            }
        }
        return $srCache;
    }
}
