<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\S;
use App\Models\SrConfig;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrConfigImporterService extends ImporterBase
{

    public function __construct(
        private SrConfigService $srConfigService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrConfig());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_CONFIG->value,
            'Sr Config',
            'value',
            '{property.label}: {value}',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr config to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    public function import(array $data, bool $withChildren): array
    {
        return array_map(function (S $service) {
            $this->srConfigService->getRequestConfigRepo()->setModel($service);
            return $this->srConfigService->getRequestConfigRepo()->save($service);
        }, $data);
    }

    public function importSelfNoChildren(array $map, array $data): array {
        return $this->importSelf($map, $data, false);
    }

    public function importSelfWithChildren(array $map, array $data): array {
        return $this->importSelf($map, $data, true);
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

    public function getSrConfigService(): SrConfigService
    {
        return $this->srConfigService;
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
