<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\S;
use App\Models\SrParameter;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrParameterImporterService extends ImporterBase
{

    public function __construct(
        private SrParametersService $srParametersService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrParameter());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_PARAMETER->value,
            'Sr Parameters',
            'name',
            'label',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr parameter to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (S $service) {
            $this->srParametersService->getRequestParametersRepo()->setModel($service);
            return $this->srParametersService->getRequestParametersRepo()->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {
        foreach ($data as $parameter) {
            if (empty($parameter['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request name is required."
                );
            }
            if (empty($parameter['value'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request label is required."
                );
            }
        }
    }

    public function filterImportData(array $data): array {
        $filter =  array_filter($data, function ($sr) {
            return (
                !empty($sr['name']) &&
                !empty($sr['value'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
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

    public function getSrParametersService(): SrParametersService
    {
        return $this->srParametersService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function getExportTypeData($item): array|bool
    {
        return false;
    }
}
