<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\RateLimitService;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrImporterService extends ImporterBase
{

    public function __construct(
        private SrService $srService,
        private SrConfigImporterService $srConfigImporterService,
        private SrParameterImporterService $srParameterImporterService,
        private SrResponseKeysImporterService $srResponseKeysImporterService,
        private SrRateLimitImporterService $srRateLimitImporterService,
        private SrScheduleImporterService $srScheduleImporterService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            "show" => true,
            'name' => 'srs',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new S());
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (S $service) {
            $this->srService->getServiceRequestRepository()->setModel($service);
            return $this->srService->getServiceRequestRepository()->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {
        foreach ($data as $sr) {
            if (empty($sr['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request name is required."
                );
            }
            if (empty($sr['label'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request label is required."
                );
            }

            if (
                !empty($sr['sr_rate_limit']) &&
                is_array($sr['sr_rate_limit'])
            ) {
                $this->srRateLimitImporterService->validateImportData($sr['sr_rate_limit']);
            }
            if (
                !empty($sr['sr_schedule']) &&
                is_array($sr['sr_schedule'])
            ) {
                $this->srScheduleImporterService->validateImportData($sr['sr_schedule']);
            }
            if (
                !empty($sr['sr_response_keys']) &&
                is_array($sr['sr_response_keys'])
            ) {
                $this->srResponseKeysImporterService->validateImportData($sr['sr_response_keys']);
            }
            if (
                !empty($sr['sr_parameter']) &&
                is_array($sr['sr_parameter'])
            ) {
                $this->srParameterImporterService->validateImportData($sr['sr_parameter']);
            }
            if (
                !empty($sr['sr_config']) &&
                is_array($sr['sr_config'])
            ) {
                $this->srConfigImporterService->validateImportData($sr['sr_config']);
            }
            if (
                !empty($sr['child_srs']) &&
                is_array($sr['child_srs'])
            ) {
                $this->validateImportData($sr['child_srs']);
            }
        }
    }

    public function filterData(array $data): array {
        $filterSrs = array_filter($data, function ($sr) {
            return (
                !empty($sr['name']) &&
                !empty($sr['label'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return array_map(function ($sr) {
            if (
                !empty($sr['child_srs']) &&
                is_array($sr['child_srs'])
            ) {
                $sr['child_srs'] = $this->filterData($sr['child_srs']);
            }
            return $sr;
        }, $filterSrs);
    }
    public function filterImportData(array $data): array {
        return [
            'type' => 'srs',
            'data' => $this->parseEntityBatch(
                $this->filterData($data)
            )
        ];
    }
    public function parseEntity(array $entity): array {
        if (
            !empty($entity['sr_rate_limit']) &&
            is_array($entity['sr_rate_limit'])
        ) {
            $entity['sr_rate_limit'] = $this->srRateLimitImporterService->filterImportData($entity['sr_rate_limit']);
        }
        if (
            !empty($entity['sr_schedule']) &&
            is_array($entity['sr_schedule'])
        ) {
            $entity['sr_schedule'] = $this->srScheduleImporterService->filterImportData($entity['sr_schedule']);
        }
        if (
            !empty($entity['sr_response_keys']) &&
            is_array($entity['sr_response_keys'])
        ) {
            $entity['sr_response_keys'] = $this->srResponseKeysImporterService->filterImportData($entity['sr_response_keys']);
        }
        if (
            !empty($entity['sr_parameter']) &&
            is_array($entity['sr_parameter'])
        ) {
            $entity['sr_parameter'] = $this->srParameterImporterService->filterImportData($entity['sr_parameter']);
        }
        if (
            !empty($entity['sr_config']) &&
            is_array($entity['sr_config'])
        ) {
            $entity['sr_config'] = $this->srConfigImporterService->filterImportData($entity['sr_config']);
        }
        if (
            !empty($entity['child_srs']) &&
            is_array($entity['child_srs'])
        ) {
            $entity['child_srs'] = $this->filterImportData($entity['child_srs']);
        }
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }


    public function getSrService(): SrService
    {
        return $this->srService;
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
