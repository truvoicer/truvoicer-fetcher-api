<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Models\SrParameter;
use App\Services\ApiServices\ServiceRequests\SrParametersService;

class SrParameterImporterService extends ImporterBase
{

    public function __construct(
        private SrParametersService $srParametersService,
    )
    {
        parent::__construct(new SrParameter());
    }

    public function import(array $data, array $mappings = [])
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
        return array_filter($data, function ($sr) {
            return (
                !empty($sr['name']) &&
                !empty($sr['value'])
            );
        }, ARRAY_FILTER_USE_BOTH);

    }

    public function getSrParametersService(): SrParametersService
    {
        return $this->srParametersService;
    }

}
