<?php

namespace App\Services\Tools\Importer\Entities;

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
        $this->setConfig([
            'name' => 'sr_config',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new SrConfig());
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (S $service) {
            $this->srConfigService->getRequestConfigRepo()->setModel($service);
            return $this->srConfigService->getRequestConfigRepo()->save($service);
        }, $data);
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
            'type' => 'sr_config',
            'data' => $this->parseEntity($data)
        ];
    }

    public function parseEntity(array $entity): array {
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
