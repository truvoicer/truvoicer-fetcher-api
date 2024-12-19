<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use Exception;
use Illuminate\Database\Eloquent\Model;

class ProviderPropertiesImporterService extends ImporterBase
{

    public function __construct(
        private PropertyImporterService $propertyImporterService,
        protected AccessControlService  $accessControlService
    )
    {
        parent::__construct($accessControlService, new ProviderProperty());
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

    protected function loadDependencies(): void
    {
        $this->providerService->setThrowException(false);
        $this->propertyImporterService->setUser($this->getUser());
    }

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        $provider = $this->findProvider(ImportType::PROVIDER_PROPERTY, $data, $map, $dest);
        if (!$provider['success']) {
            return $provider;
        }
        $provider = $provider['provider'];

        $property = $this->findProperty($provider, $data, $map);
        if (!$property['success']) {
            return $property;
        }
        $property = $property['property'];

        if (!$this->entityService->lockEntity($this->getUser(), $property->id, ProviderProperty::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock provider property {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider property import is locked.'
        ];
    }
    public function unlock(ProviderProperty $providerProperty): array
    {
        if (!$this->entityService->unlockEntity($this->getUser(), $providerProperty->id, ProviderProperty::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock provider property {$providerProperty->name}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider property import is locked.'
        ];
    }

    private function saveProviderProperty(array $data, array $map, ?array $dest = null): array{
        $provider = $this->findProvider(ImportType::PROVIDER_PROPERTY, $data, $map, $dest);
        if (!$provider['success']) {
            return $provider;
        }
        $provider = $provider['provider'];

        $property = $this->findProperty($provider, $data, $map);
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
        $unlock = $this->unlock(
            $this->providerService->getProviderPropertyRepository()->getModel()
        );
        if (!$unlock['success']) {
            return $unlock;
        }
        return [
            'success' => true,
            'message' => "Provider property: {$data['name']} for provider {$provider->name} imported successfully."
        ];
    }
    private function findProperty(Provider $provider, array $data, array $map): array
    {
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
                false,
                $map
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

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            return $this->saveProviderProperty($data, $map, $dest);
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            return $this->saveProviderProperty($data, $map, $dest);
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getImportMappings(array $data): array
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

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['properties', 'provider_rate_limit'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::PROVIDER_PROPERTY => (!empty($item['properties']))? $item['properties'] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }
    public function getExportTypeData($item): array|bool
    {
        return [];
    }
}
