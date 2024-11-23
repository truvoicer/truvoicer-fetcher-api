<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
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
        private ProviderService $providerService,
        protected AccessControlService $accessControlService
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

    public function import(array $data, array $mappings = []): array
    {
        return [];
    }

    public function importSelfNoChildren(array $map, array $data): array {

        return [
            'success' => true,
        ];
    }

    public function importSelfWithChildren(array $map, array $data): array {

        return [
            'success' => true,
        ];
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

    public function parseEntity(array $entity): array {
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
