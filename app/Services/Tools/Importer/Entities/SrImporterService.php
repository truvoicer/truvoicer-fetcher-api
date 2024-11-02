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
            'name' => 'srs',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new S());
    }

    public function import(array $data, array $mappings = [])
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

    public function filterImportData(array $data): array {
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
                $sr['child_srs'] = $this->filterImportData($sr['child_srs']);
            }
            return $sr;
        }, $filterSrs);
    }


    public function getSrService(): SrService
    {
        return $this->srService;
    }

}
