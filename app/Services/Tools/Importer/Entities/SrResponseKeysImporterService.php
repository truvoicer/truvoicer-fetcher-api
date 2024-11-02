<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\Permission\AccessControlService;

class SrResponseKeysImporterService extends ImporterBase
{

    public function __construct(
        private SrResponseKeyService $responseKeyService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            'name' => 'sr_response_keys',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new SrResponseKey());
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (S $service) {
            $this->responseKeyService->getSrResponseKeyRepository()->setModel($service);
            return $this->responseKeyService->getSrResponseKeyRepository()->save($service);
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

    public function getResponseKeyService(): SrResponseKeyService
    {
        return $this->responseKeyService;
    }

}
