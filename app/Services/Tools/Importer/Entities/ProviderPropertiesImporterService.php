<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\S;
use App\Models\SrConfig;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Model;

class ProviderPropertiesImporterService extends ImporterBase
{

    public function __construct(
        private ProviderService         $providerService,
        private PropertyImporterService $propertyImporterService,
        protected AccessControlService  $accessControlService
    )
    {
        parent::__construct($accessControlService, new ProviderProperty());
        $this->providerService->setThrowException(false);
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::PROVIDER_PROPERTY->value,
            'Provider Properties',
            'value',
            '{label}: {pivot.value}',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import provider property to provider',
                'dest' => ImportType::PROVIDER->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    private function findProvider(array $data): array
    {
        if (!empty($data['provider'])) {
            $provider = $data['provider'];
        } elseif (!empty($data['provider_id'])) {
            $provider = $this->providerService->getProviderById((int)$data['provider_id']);
        } else {
            return [
                'success' => false,
                'message' => "Provider is required for provider id: {$data['provider_id']} property: {$data['name']}."
            ];
        }
        if (!$provider instanceof Provider) {
            return [
                'success' => false,
                'message' => "Provider not found for provider id: {$data['provider_id']} property: {$data['name']}"
            ];
        }
        return [
            'success' => true,
            'provider' => $provider
        ];
    }
    private function saveProviderProperty(array $data): array{
        $provider = $this->findProvider($data);
        if (!$provider['success']) {
            return $provider;
        }
        $provider = $provider['provider'];

        $property = $this->findProperty($provider, $data);
        if (!$property['success']) {
            return $property;
        }
        $property = $property['property'];

        if (!$this->providerService->createProviderProperty($provider, $property, array_merge($data, $data['pivot']))) {
            return [
                'success' => false,
                'message' => "Failed to create provider property: {$data['name']} for provider {$provider->name}."
            ];
        }
        return [
            'success' => true,
            'message' => "Provider property: {$data['name']} for provider {$provider->name} imported successfully."
        ];
    }
    private function findProperty(Provider $provider, array $data): array
    {
        $this->propertyImporterService->setUser($this->getUser());
        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => "Provider property key name is required for provider {$provider->name} property: {$data['name']}."
            ];
        }
        if (empty($data['pivot']) || !is_array($data['pivot']) || !count($data['pivot'])) {
            return [
                'success' => false,
                'message' => "Provider property pivot is required for provider {$provider->name} property: {$data['name']}."
            ];
        }
        $this->propertyImporterService->getPropertyService()->getPropertyRepository()->addWhere(
            'name',
            $data['name']
        );
        $property = $this->propertyImporterService->getPropertyService()->getPropertyRepository()->findOne();
        if (!$property instanceof Model) {
            $property = $this->propertyImporterService->import(
                ImportAction::OVERWRITE,
                $data,
                false
            );
            if (!$property['success']) {
                return $property;
            }
            $property = $this->propertyImporterService->getPropertyService()->getPropertyRepository()->getModel();
        }
        return [
            'success' => true,
            'property' => $property
        ];
    }

    protected function overwrite(array $data, bool $withChildren): array
    {
        try {
            return $this->saveProviderProperty($data);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
            return $this->saveProviderProperty($data);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }

    public function validateImportData(array $data): void
    {
        if (empty($sr['value']) && empty($sr['array_value'])) {
            $this->addError(
                'import_type_validation',
                "Service Request name is required."
            );
        }
    }

    public function filterImportData(array $data): array
    {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($data)
        ];
    }

    public function parseEntity(array $entity): array
    {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getProviderService(): ProviderService
    {
        return $this->providerService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function getExportTypeData($item): array|bool
    {
        return [];
    }
}
