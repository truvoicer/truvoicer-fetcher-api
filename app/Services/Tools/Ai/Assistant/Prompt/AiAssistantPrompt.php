<?php

namespace App\Services\Tools\Ai\Assistant\Prompt;

use App\Enums\Ai\AiClient;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Provider\CreateProviderRequest;
use App\Http\Requests\Provider\Property\SaveProviderPropertyRequest;
use App\Http\Requests\Service\CreateSRequest;
use App\Http\Requests\Service\Request\Config\CreateServiceRequestConfigRequest;
use App\Http\Requests\Service\Request\CreateSrRequest;
use App\Http\Requests\Service\Request\Parameter\CreateServiceRequestParameterRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Truvoicer\TfDbReadCore\Enums\Property\PropertyType;
use Truvoicer\TfDbReadCore\Services\ApiManager\Client\ApiClientHandler;
use Truvoicer\TfDbReadCore\Services\ApiManager\Client\Entity\ApiRequest;
use Truvoicer\TfDbReadCore\Services\ApiManager\Response\ResponseManager;

class AiAssistantPrompt
{
    protected ?AiClient $aiClient = null;

    protected array $serviceRules = [];

    protected array $categoryRules = [];

    protected array $provider = [];

    protected array $properties = [];

    protected array $propertyValueRules = [];

    protected array $srRules = [];

    protected array $srConfigRules = [];

    protected array $srParameterRules = [];

    public function __construct()
    {
        $this->init();
    }

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

    public function init()
    {

        $this->serviceRules = $this->parseRules((new CreateSRequest)->rules());
        $this->categoryRules = $this->parseRules((new CreateCategoryRequest)->rules());
        $this->provider = $this->parseRules((new CreateProviderRequest)->rules());
        $this->properties = array_map(function (PropertyType $propertyType) {
            return $propertyType->config();
        }, PropertyType::cases());
        $this->propertyValueRules = $this->parseRules((new SaveProviderPropertyRequest)->rules());
        $this->srRules = $this->parseRules((new CreateSrRequest)->rules());
        $this->srConfigRules = $this->parseRules((new CreateServiceRequestConfigRequest)->rules());
        $this->srParameterRules = $this->parseRules((new CreateServiceRequestParameterRequest)->rules());
    }

    private function parseRules(array $rules)
    {
        return array_filter($rules, function ($rule) {
            return is_string($rule);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Convert array to JSON with error handling
     */
    protected function toJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Failed to encode data to JSON: '.json_last_error_msg());
        }

        return $json;
    }

    public function makeRequest(string $prompt): array|bool
    {
        $prompt = $this->prompt($prompt);

        $apiClient = app(ApiClientHandler::class);
        $apiRequest = app(ApiRequest::class);

        $aiClientEnum = $this->aiClient;
        if (! $aiClientEnum) {
            throw new RuntimeException('AI client is invalid enum.');
        }
        $apiTypeEnum = $aiClientEnum->apiType();
        $apiRequest->setApiType(
            $apiTypeEnum
        );

        $accessToken = $aiClientEnum->apiKey();
        if ($accessToken) {
            $apiRequest->setAccessToken(
                $accessToken
            );
        }

        $apiRequest->setAiPrompt(
            $prompt
        );

        try {
            $response = $apiClient->sendRequest($apiRequest);

            $responseManager = app(ResponseManager::class)->setApiType($apiTypeEnum);

            return $responseManager->getJsonBody($response);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function getServiceRules(): array
    {
        return $this->serviceRules;
    }

    public function setServiceRules(array $serviceRules): self
    {
        $this->serviceRules = $serviceRules;

        return $this;
    }

    public function getCategoryRules(): array
    {
        return $this->categoryRules;
    }

    public function setCategoryRules(array $categoryRules): self
    {
        $this->categoryRules = $categoryRules;

        return $this;
    }

    public function getProvider(): array
    {
        return $this->provider;
    }

    public function setProvider(array $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function getPropertyValueRules(): array
    {
        return $this->propertyValueRules;
    }

    public function setPropertyValueRules(array $propertyValueRules): self
    {
        $this->propertyValueRules = $propertyValueRules;

        return $this;
    }

    public function getSrRules(): array
    {
        return $this->srRules;
    }

    public function setSrRules(array $srRules): self
    {
        $this->srRules = $srRules;

        return $this;
    }

    public function getSrConfigRules(): array
    {
        return $this->srConfigRules;
    }

    public function setSrConfigRules(array $srConfigRules): self
    {
        $this->srConfigRules = $srConfigRules;

        return $this;
    }

    public function getSrParameterRules(): array
    {
        return $this->srParameterRules;
    }

    public function setSrParameterRules(array $srParameterRules): self
    {
        $this->srParameterRules = $srParameterRules;

        return $this;
    }

    public function prompt(string $prompt): string
    {
        $propertiesJson = $this->toJson($this->properties);
        $requiredPropertiesJson = $this->toJson(
            array_map(function (PropertyType $propertyType) {
                return $propertyType->config();
            }, [
                PropertyType::API_AUTH_TYPE,
                PropertyType::METHOD,
                PropertyType::API_TYPE,
                PropertyType::RESPONSE_FORMAT,
            ])
        );

        return <<<PROMPT
Prompt: ($prompt).


You are an intelligent data finder and fetching tool. Your task is to find api's, api services, rss feed configurations relating to the prompt above. The configurations you find cannot be example or test ones, they must be live and free (no payment required).

BRIEF:
The system you are fetching data for is an external api provider management system, it performs api requests to external api's or rss feeds. It provides api response data management for external provider api's and consolidates api responses/data to one format for better data processing with all providers by using the api response keys manager.

It is structured with the following entities:

- Service
A service allows for the grouping of api requests and consolidated response keys. A service is related a parent of a service request, and the response keys of a service are the keys used in the child service requests. The child service request set their own values for the response keys, but the keys are from the parent service.

- Service Response Key
Defines the standardized keys that belong to a service, which all child service requests must use as their response key template, ensuring consistent data structure across different providers.

- Category
Allows for grouping of providers, provider can have many categories.

- Property
A property is relating to a common property for configuration of the api request to the external api provider. e.g. request_method, authentication_type etc.

- Provider
A provider is an external api provider, like facebook-api.com or steam-api.com. It is a container/parent for service requests. It is related to categories and can have many categories.

- Provider Property
A provider property is a property that is added as a relation to a provider. e.g. property request_method is given a value of post and related to the specific provider.

- Service Request
Represents a specific API endpoint request for a provider, defining how to communicate with the external API endpoint including the endpoint path, HTTP method, and which service it belongs to.

- Service Request Config
Holds configuration properties for a service request, it's an override for the provider properties.

- Service Request Config Property
A service request config property is a property that is added as a relation to a service request config. e.g. property request_method is given a value of post and related to the specific service request config.

- Service Request Parameter
Defines the parameters that can be called with placeholders.

- Service Request Response Key
Maps the actual API response field paths from a specific provider to the standardized service response keys, effectively translating external API responses into the unified format.

CRITICAL RULES:
1. **FREE & LIVE ONLY**: You MUST ONLY find APIs that are completely free (no credit card required, no paid tiers needed for basic access) and currently operational. Verify by checking official documentation.

2. **COMPREHENSIVE COVERAGE**: You must identify ALL relevant API endpoints for the requested service. Don't miss authentication endpoints, data endpoints, or utility endpoints.

3. **PARAMETER COMPLETENESS**: All required parameters documented by the API must be included. Optional but commonly used parameters should also be included.

4. **NO HALLUCINATION**: Never invent endpoints, parameters, or response fields that aren't documented by the official API. If documentation is unclear, omit rather than guess.

5. **FOLLOW RULES STRICTLY**: All generated data MUST conform to the validation rules provided in the JSON schemas. Each field must satisfy its validation requirements.

DECISION PROCESS:
Follow this step-by-step reasoning for each API you analyze:

**Step 1 - API Discovery**: Identify the official API provider for the requested topic. Find the base URL, documentation site, and API version.

**Step 2 - Authentication Analysis**: Determine the authentication method (API key, OAuth, none) and categorize it as a provider property.

**Step 3 - Service Definition**: Define the parent service that groups related requests. Choose a logical name based on the API's purpose.

**Step 4 - Request Mapping**:
   - For each endpoint, create a service request
   - Document the exact endpoint path, HTTP method, and required headers
   - Identify URL parameters, query parameters, and body parameters

**Step 5 - Parameter Extraction**:
   - Extract all parameters from documentation
   - Distinguish between required and optional
   - Identify default values and accepted formats

**Step 6 - Validation**: Verify all data fits within the validation rules of each entity

GOOD PRACTICES:
- Thorough Documentation Review**: Read the entire API documentation, not just the first endpoint
- Example-Based Analysis**: Use API response examples to identify all possible fields
- Error Checking**: Note common error responses in the configuration
- Versioning**: Specify API version in the endpoint path or headers
- Have a base url property as a provider property and endpoint property as a service config property
- For rss feeds, the provider property base url should be the domain and the service config property endpoint should be the part of the url more specific to the rss feed. For example (this is only an example to explain good standards when deciding what to use as the base url and endpoint), if the found rss feed url was https://feeds.skynews.com/feeds/rss/home.xml, the provider property base url should be https://feeds.skynews.com/feeds/rss and the service config property endpoint should be /home.xml. Apply this kind of logic.

BAD PRACTICES TO AVOID:
- Mixing Paid APIs**: NEVER include APIs that require payment, even if they have a free tier with limitations
- Ignoring Required Fields**: Never omit required parameters just because they're "common sense"
- Using Example Data**: Never use example.com or placeholder endpoints from documentation examples
- Case Insensitivity Mismatch**: Don't ignore case sensitivity in parameter names or response fields
- Missing Authentication**: Never forget to include authentication requirements
- Assuming Defaults**: Don't assume parameter defaults without documentation confirmation
- Inconsistent Naming**: Avoid mixing naming conventions (camelCase, PascalCase, snake_case) within the same service
- Forgetting Placeholders**: Never hardcode values that should be dynamic parameters

AVAILABLE PROPERTIES:
The following json shows the available properties and the data type of that property and available choices if needed, you can only pick from these properties for providerProperties and serviceRequestConfigProperties. You don't have to use all, just pick the ones you need.
{
     "available_properties": $propertiesJson,
}

The following properties are required and must be in the config for providerProperties:
$requiredPropertiesJson

IMPORTANT:
You can only pick from the available_properties above for providerProperties and serviceRequestConfigProperties. You don't have to use all, just pick the ones you need. In the description provide links to the api's website, company, documentation etc. and be descriptive. The description can be in html format for better presentation.

Your task:
1. Return a JSON object in this format (the values must adhere to these rules):

    {
        "label: string,
        "description: string,
        "service": {
            "name": string,
            "label": string
        },
        "category": {
            "name": string,
            "label": string
        },
        "provider": {
            "name": string,
            "label": string,
            "global": boolean,
            "categories": [
                {
                    "name": string,
                    "label": string
                }
            ]
        },
        "providerProperties": [
            {
                "property": string|property_name,
                "value": string|sometimes,
                "array_value": array|sometimes,
                "big_text_value": text|sometimes
            }
        ],
        "serviceRequest": [
            {
                "default_sr": boolean,
                "name": string,
                "label": string,
                "service": {
                    "name": string,
                    "label": string
                },
                "category": [
                    {
                        "name": string,
                        "label": string
                    }
                ],
                "query_parameters": array<string>
            }
        ],
        "serviceRequestConfigProperties": [
            {
                "service_request_name": string,
                "properties": [
                    {
                        "property_name": string|e.g headers,
                        "array_value": {
                            "Accept": "application/json",
                            "Authorization": "Bearer {access_token}"
                        }
                    },
                    {
                        "property_name": string|e.g request_timeout,
                        "value": string|integer
                    }
                ]
            },
        ],
        "serviceRequestParameters": [
            {
                "name": string,
                "value": string,
                "service_request_name": string
            }
        ]
    }

PROMPT;
    }
}
