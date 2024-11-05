<?php

namespace App\Services\Tools\Importer\Entities;

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

        $this->setConfig([
            "show" => false,
            'name' => 'sr_parameters',
            "label" => "Sr Parameters",
            "id" => "id",
            "nameField" => "name",
            "labelField" => "label",
            'import_mappings' => [
                [
                    'name' => 'no_children',
                    'label' => 'No Children',
                    'source' => 'sr_parameters',
                    'dest' => 'sr_parameters',
                ],
                [
                    'name' => 'include_children',
                    'label' => 'Include Children',
                    'source' => 'sr_parameters',
                    'dest' => 'sr_parameters',
                ],
            ],
        ]);
        parent::__construct($accessControlService, new SrParameter());
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
            'import_type' => 'sr_parameters',
            'label' => 'Sr Parameters',
            'children' => $this->parseEntityBatch($filter)
        ];

    }
    public function parseEntity(array $entity): array {
        $entity['import_type'] = 'sr_parameters';
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
