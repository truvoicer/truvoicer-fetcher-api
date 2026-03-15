<?php

namespace App\Services\Tools\Ai\Assistant;

use App\Enums\Ai\AiClient;
use App\Models\AiImportConfig;
use App\Models\AiImportPrompt;
use App\Repositories\AiImportConfigRepository;
use App\Services\Tools\Ai\Assistant\Prompt\DeepSeek\AssistantDeepSeekPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\Gemini\AssistantGeminiPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\Grok\AssistantGrokPrompt;
use App\Services\Tools\Ai\Assistant\Prompt\OpenAi\AssistantOpenAiPrompt;
use App\Services\Tools\Ai\Import\Prompt\AiImportPromptService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
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

    // Track name mappings for all entity types
    private array $nameMappings = [
        'service' => [],
        'category' => [],
        'provider' => [],
        'service_request' => []
    ];

    // Stack to track created entities for rollback
    private array $createdEntities = [
        'services' => [],
        'categories' => [],
        'providers' => [],
        'provider_properties' => [],
        'service_requests' => [],
        'sr_configs' => [],
        'sr_parameters' => [],
        'category_attachments' => [] // Track many-to-many attachments
    ];

    public function __construct(
        private ApiService $sService,
        private PropertyService $propertyService,
        private CategoryService $categoryService,
        private ProviderService $providerService,
        private SrService $srService,
        private SrConfigService $srConfigService,
        private SrParametersService $srParametersService,
        private AiImportConfigRepository $aiImportConfigRepository,
        private AiImportPromptService $aiImportPromptService,
    ) {}

    public function build(string $prompt, ?AiClient $aiClient = AiClient::GEMINI)
    {
        $this->aiImportPromptService->setUser($this->user);
        if (
            !AiImportPrompt::where('prompt', $prompt)->exists()
        ) {
            $this->aiImportPromptService->createAiImportPrompt([
                'prompt' => $prompt
            ]);
        }
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

    public function updateAiImportConfig(AiImportConfig $aiImportConfig, array $data): bool
    {
        return $this->aiImportConfigRepository->updateImportConfig($aiImportConfig, $data);
    }

    public function deleteAiImportConfig(AiImportConfig $aiImportConfig): bool
    {
        return $this->aiImportConfigRepository->setModel($aiImportConfig)->delete();
    }

    public function deleteBulkAiImportConfigs(array $ids): bool
    {
        return $this->aiImportConfigRepository->deleteBatch($ids);
    }

    /**
     * Rollback all created entities in reverse order
     */
    private function rollback(): void
    {
        // Rollback in reverse order of creation

        // First, detach categories (many-to-many)
        foreach (array_reverse($this->createdEntities['category_attachments']) as $attachment) {
            try {
                $attachment['entity']->categories()->detach($attachment['category_id']);
            } catch (\Exception $e) {
                // Log but continue rollback
                logger()->error('Failed to detach category', [
                    'error' => $e->getMessage(),
                    'attachment' => $attachment
                ]);
            }
        }

        // Delete SR parameters
        foreach (array_reverse($this->createdEntities['sr_parameters']) as $parameter) {
            try {
                $this->srParametersService->deleteRequestParameter($parameter);
            } catch (\Exception $e) {
                logger()->error('Failed to delete SR parameter', [
                    'error' => $e->getMessage(),
                    'parameter_id' => $parameter->id
                ]);
            }
        }

        // Delete SR configs
        foreach (array_reverse($this->createdEntities['sr_configs']) as $config) {
            try {
                $this->srConfigService->getRequestConfigRepo()->setModel($config)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete SR config', [
                    'error' => $e->getMessage(),
                    'config_id' => $config->id
                ]);
            }
        }

        // Delete service requests
        foreach (array_reverse($this->createdEntities['service_requests']) as $sr) {
            try {
                $this->srService->getServiceRequestRepository()->setModel($sr)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete service request', [
                    'error' => $e->getMessage(),
                    'sr_id' => $sr->id
                ]);
            }
        }

        // Delete provider properties
        foreach (array_reverse($this->createdEntities['provider_properties']) as $property) {
            try {
                $this->providerService->getProviderPropertyRepository()->setModel($property)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete provider property', [
                    'error' => $e->getMessage(),
                    'property_id' => $property->id
                ]);
            }
        }

        // Delete providers
        foreach (array_reverse($this->createdEntities['providers']) as $provider) {
            try {
                $this->providerService->getProviderRepository()->setModel($provider)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete provider', [
                    'error' => $e->getMessage(),
                    'provider_id' => $provider->id
                ]);
            }
        }

        // Delete categories
        foreach (array_reverse($this->createdEntities['categories']) as $category) {
            try {
                $this->categoryService->getCategoryRepository()->setModel($category)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete category', [
                    'error' => $e->getMessage(),
                    'category_id' => $category->id
                ]);
            }
        }

        // Delete services
        foreach (array_reverse($this->createdEntities['services']) as $service) {
            try {
                $this->sService->getServiceRepository()->setModel($service)->delete();
            } catch (\Exception $e) {
                logger()->error('Failed to delete service', [
                    'error' => $e->getMessage(),
                    'service_id' => $service->id
                ]);
            }
        }

        // Clear the created entities array
        $this->createdEntities = [
            'services' => [],
            'categories' => [],
            'providers' => [],
            'provider_properties' => [],
            'service_requests' => [],
            'sr_configs' => [],
            'sr_parameters' => [],
            'category_attachments' => []
        ];
    }

    /**
     * Execute a callback with automatic rollback on failure
     */
    private function withRollback(callable $callback)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function makeImport(AiImportConfig $aiImportConfig): void
    {
        $this->withRollback(function () use ($aiImportConfig) {
            // Reset name mappings for each import
            $this->nameMappings = [
                'service' => [],
                'category' => [],
                'provider' => [],
                'service_request' => []
            ];

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
        });
    }

    /**
     * Get the updated name for an entity if it was renamed
     */
    private function getUpdatedName(string $type, string $originalName): string
    {
        return $this->nameMappings[$type][$originalName] ?? $originalName;
    }

    /**
     * Recursively update all name references in an array
     */
    private function updateNameReferences(array $data, string $type, string $oldName, string $newName): array
    {
        array_walk_recursive($data, function (&$value, $key) use ($type, $oldName, $newName) {
            // Update service name references
            if ($type === 'service' && $key === 'name' && $value === $oldName) {
                $value = $newName;
            }

            // Update category name references
            if ($type === 'category' && $key === 'name' && $value === $oldName) {
                $value = $newName;
            }

            // Update service request name references
            if ($type === 'service_request' && ($key === 'service_request_name' || $key === 'name') && $value === $oldName) {
                $value = $newName;
            }

            // Update provider name references in categories array
            if ($type === 'provider' && $key === 'name' && $value === $oldName) {
                $value = $newName;
            }
        });

        return $data;
    }

    private function handleService(array $data): S|null
    {
        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in service data. | json: (' . json_encode($data) . ')'
            );
        }

        $originalName = $data['name'] ?? Str::slug($data['label']);

        if (empty($data['name'])) {
            $data['name'] = $originalName;
        }

        $existing = S::where('name', $data['name'])->first();
        if ($existing instanceof S) {
            $newName = $existing->name;

            // Store the name mapping
            $this->nameMappings['service'][$originalName] = $newName;
            return $existing;
        } else {
            $this->nameMappings['service'][$originalName] = $data['name'];
        }

        if (!$this->sService->createService($this->user, $data)) {
            throw new Exception(
                'Error storing service. | json: (' . json_encode($data) . ')'
            );
        }

        $service = $this->sService->getServiceRepository()->getModel();

        // Track created service for rollback
        $this->createdEntities['services'][] = $service;

        return $service;
    }

    private function handleCategory(array $data): Category|null
    {
        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in category data. | json: (' . json_encode($data) . ')'
            );
        }

        $originalName = $data['name'] ?? Str::slug($data['label']);

        if (empty($data['name'])) {
            $data['name'] = $originalName;
        }

        $existing = Category::where('name', $data['name'])->first();
        if ($existing instanceof Category) {
            $newName = $existing->name;
            // Store the name mapping
            $this->nameMappings['category'][$originalName] = $newName;
            return $existing;
        } else {
            $this->nameMappings['category'][$originalName] = $data['name'];
        }

        if (!$this->categoryService->createCategory($this->user, $data)) {
            throw new Exception(
                'Error storing category. | json: (' . json_encode($data) . ')'
            );
        }

        $category = $this->categoryService->getCategoryRepository()->getModel();

        // Track created category for rollback
        $this->createdEntities['categories'][] = $category;

        return $category;
    }

    private function prepareProviderPropertySaveData(array $data)
    {
        if (
            !array_key_exists('value', $data) &&
            !array_key_exists('array_value', $data) &&
            !array_key_exists('big_text_value', $data)
        ) {
            throw new Exception(
                'Provider property has an missing value. | json: (' . json_encode($data) . ')'
            );
        }

        if (array_key_exists('value', $data)) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT;
            $data['value'] = (string)$data['value'];
        } elseif (!empty($data['array_value']) && is_array($data['array_value'])) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST;
        } elseif (array_key_exists('big_text_value', $data)) {
            $data['value_type'] = DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT;
            $data['big_text_value'] = (string)$data['big_text_value'];
        }

        return $data;
    }

    private function handleProvider(array $data, array $providerProperties): Provider|null
    {
        if (empty($data['label'])) {
            throw new Exception(
                'Label (label) not found in provider data. | json: (' . json_encode($data) . ')'
            );
        }

        $originalName = $data['name'] ?? Str::slug($data['label']);

        if (empty($data['name'])) {
            $data['name'] = $originalName;
        }

        // Update category references in provider categories array
        if (!empty($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as &$category) {
                if (isset($category['name'])) {
                    $category['name'] = $this->getUpdatedName('category', $category['name']);
                }
            }
        }

        $existing = Provider::where('name', $data['name']);
        if ($existing->first() instanceof Provider) {
            $newName = $this->providerService->getProviderRepository()->buildCloneEntityStr(
                $existing,
                'name',
                $data['name']
            );

            // Store the name mapping
            $this->nameMappings['provider'][$originalName] = $newName;

            $data['label'] = $data['name'] = $newName;
        } else {
            $this->nameMappings['provider'][$originalName] = $data['name'];
        }

        if (!$this->providerService->createProvider($this->user, $data)) {
            throw new Exception(
                'Error storing provider. | json: (' . json_encode($data) . ')'
            );
        }

        $provider = $this->providerService->getProviderRepository()->getModel();

        // Track created provider for rollback
        $this->createdEntities['providers'][] = $provider;

        $categories = (!empty($data['categories']) && is_array($data['categories']))
            ? $data['categories'] : [];

        foreach ($categories as $category) {
            if (!empty($category['name'])) {
                $categoryName = $category['name'];
            } else {
                throw new Exception(
                    'Category has no name. | json: (' . json_encode($category) . ')'
                );
            }

            $findCategory = $this->categoryService->getCategoryRepository()->findByName($categoryName);
            if (!$findCategory) {
                $createCategory = $this->categoryService->createCategory(
                    $this->user,
                    [
                        'name' => $categoryName,
                        'label' => Str::title($categoryName)
                    ]
                );
                if (!$createCategory) {
                    continue;
                }
                $findCategory = $this->categoryService->getCategoryRepository()->getModel();
            }

            if (
                !$provider->categories()
                    ->where('categories.id', $findCategory->id)
                    ->exists()
            ) {
                $provider->categories()->attach($findCategory->id);

                // Track category attachment for rollback
                $this->createdEntities['category_attachments'][] = [
                    'entity' => $provider,
                    'category_id' => $findCategory->id,
                    'type' => 'provider'
                ];
            }
        }

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
                $this->prepareProviderPropertySaveData($providerProperty)
            );

            if (!$create) {
                throw new Exception(
                    'Error storing provider property. | json: (' . json_encode($providerProperty) . ')'
                );
            }

            // Track created provider property for rollback
            // Note: You'll need to get the created property model from the service
            // This assumes the service has a getModel() method or returns the model
            if (method_exists($this->providerService, 'getProviderPropertyRepository')) {
                $property = $this->providerService->getProviderPropertyRepository()->getModel();
                $this->createdEntities['provider_properties'][] = $property;
            }
        }

        return $provider;
    }

    private function handleServiceRequest(Provider $provider, array $srs, array $serviceRequestConfigProperties, array $serviceRequestParameters): Collection
    {
        $srCache = collect();

        // First pass: Create all service requests and track name mappings
        foreach ($srs as $index => $srData) {
            if (empty($srData['label'])) {
                throw new Exception(
                    'Label (label) not found in service request data. | json: (' . json_encode($srData) . ')'
                );
            }

            $originalName = $srData['name'] ?? Str::slug($srData['label']);

            if (empty($srData['name'])) {
                $srData['name'] = $originalName;
            }

            // Update service name reference if it was renamed
            if (!empty($srData['service']) && isset($srData['service']['name'])) {
                $srData['service']['name'] = $this->getUpdatedName('service', $srData['service']['name']);
            }

            // Update category references if they were renamed
            if (!empty($srData['category']) && is_array($srData['category'])) {
                foreach ($srData['category'] as &$category) {
                    if (isset($category['name'])) {
                        $category['name'] = $this->getUpdatedName('category', $category['name']);
                    }
                }
            }

            $existing = Sr::where('name', $srData['name']);
            if ($existing->first() instanceof Sr) {
                $newName = $this->srService->getServiceRequestRepository()->buildCloneEntityStr(
                    $existing,
                    'name',
                    $srData['name']
                );

                // Store the name mapping
                $this->nameMappings['service_request'][$originalName] = $newName;

                $srData['label'] = $srData['name'] = $newName;
            } else {
                $this->nameMappings['service_request'][$originalName] = $srData['name'];
            }

            $srData['type'] = SrType::LIST;

            if (!$this->srService->createServiceRequest($provider, $srData, false)) {
                throw new Exception(
                    'Error storing service request. | json: (' . json_encode($srData) . ')'
                );
            }
            $sr = $this->srService->getServiceRequestRepository()->getModel();
            $srCache->put($srData['name'], $sr);

            // Track created service request for rollback
            $this->createdEntities['service_requests'][] = $sr;

            $categories = (!empty($srData['category']) && is_array($srData['category']))
                ? $srData['category'] : [];

            foreach ($categories as $category) {
                if (!empty($category['name'])) {
                    $categoryName = $category['name'];
                } else {
                    throw new Exception(
                        'Category has no name. | json: (' . json_encode($category) . ')'
                    );
                }

                $findCategory = $this->categoryService->getCategoryRepository()->findByName($categoryName);
                if (!$findCategory) {
                    $createCategory = $this->categoryService->createCategory(
                        $this->user,
                        [
                            'name' => $categoryName,
                            'label' => Str::title($categoryName)
                        ]
                    );
                    if (!$createCategory) {
                        continue;
                    }
                    $findCategory = $this->categoryService->getCategoryRepository()->getModel();
                }

                if (
                    !$sr->categories()
                        ->where('categories.id', $findCategory->id)
                        ->exists()
                ) {
                    $sr->categories()->attach($findCategory->id);

                    // Track category attachment for rollback
                    $this->createdEntities['category_attachments'][] = [
                        'entity' => $sr,
                        'category_id' => $findCategory->id,
                        'type' => 'service_request'
                    ];
                }
            }
        }

        // Second pass: Update serviceRequestConfigProperties with renamed service request names
        $updatedConfigProperties = [];
        foreach ($serviceRequestConfigProperties as $configProperty) {
            if (empty($configProperty['service_request_name'])) {
                throw new Exception(
                    'Sr config property has no service_request_name. | json: (' . json_encode($configProperty) . ')'
                );
            }

            // Update the service request name if it was renamed
            $configProperty['service_request_name'] = $this->getUpdatedName(
                'service_request',
                $configProperty['service_request_name']
            );

            $updatedConfigProperties[] = $configProperty;
        }

        // Third pass: Update serviceRequestParameters with renamed service request names
        $updatedParameters = [];
        foreach ($serviceRequestParameters as $parameter) {
            if (empty($parameter['service_request_name'])) {
                throw new Exception(
                    'Sr parameter has no service_request_name. | json: (' . json_encode($parameter) . ')'
                );
            }

            // Update the service request name if it was renamed
            $parameter['service_request_name'] = $this->getUpdatedName(
                'service_request',
                $parameter['service_request_name']
            );

            $updatedParameters[] = $parameter;
        }

        // Process updated config properties
        foreach ($updatedConfigProperties as $srConfigProperty) {
            $findSr = $srCache->get($srConfigProperty['service_request_name']);
            if (!$findSr instanceof Sr) {
                throw new Exception(
                    'Sr with this name does not exist. | name:' . $srConfigProperty['service_request_name']
                );
            }

            $properties = (!empty($srConfigProperty['properties']) && is_array($srConfigProperty['properties']))
                ? $srConfigProperty['properties']
                : [];

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
                    $findSr,
                    $findProperty,
                    $this->prepareProviderPropertySaveData($propertyData)
                );

                if (!$create) {
                    throw new Exception(
                        'Error storing provider property. | json: (' . json_encode($propertyData) . ')'
                    );
                }

                // Track created SR config for rollback
                // Note: You'll need to get the created config model from the service
                if (method_exists($this->srConfigService, 'getRequestConfigRepository')) {
                    $config = $this->srConfigService->getRequestConfigRepo()->getModel();
                    $this->createdEntities['sr_configs'][] = $config;
                }
            }
        }

        // Process updated parameters
        foreach ($updatedParameters as $sRParameter) {
            $findSr = $srCache->get($sRParameter['service_request_name']);
            if (!$findSr instanceof Sr) {
                throw new Exception(
                    'Sr with this name does not exist. | name:' . $sRParameter['service_request_name']
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

            // Track created SR parameter for rollback
            // Note: You'll need to get the created parameter model from the service
            if (method_exists($this->srParametersService, 'getRequestParameterRepository')) {
                $parameter = $this->srParametersService->getRequestParametersRepo()->getModel();
                $this->createdEntities['sr_parameters'][] = $parameter;
            }
        }

        return $srCache;
    }
}
