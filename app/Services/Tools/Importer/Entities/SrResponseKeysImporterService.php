<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrResponseKeysImporterService extends ImporterBase
{

    public function __construct(
        private SrResponseKeyService $responseKeyService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            'name' => 'sr_response_keys',
            "label" => "Sr Response Keys",
            "id" => "id",
            "nameField" => "name",
            "labelField" => "name",
            'import_mappings' => [
                [
                    'name' => 'no_children',
                    'label' => 'No Children',
                    'source' => 'sr_response_keys',
                    'dest' => 'sr_response_keys',
                ],
                [
                    'name' => 'include_children',
                    'label' => 'Include Children',
                    'source' => 'sr_response_keys',
                    'dest' => 'sr_response_keys',
                ],
            ],
        ]);
        parent::__construct($accessControlService, new SrResponseKey());
    }

    public function import(array $data, array $mappings = []): array
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
        $filter = array_filter($data, function ($sr) {
            return $sr;
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'import_type' => 'sr_response_keys',
            'label' => 'Sr Response Keys',
            'name' => 'Sr Response Keys',
            'children' => $this->parseEntityBatch($filter)
        ];
    }
    public function parseEntity(array $entity): array {
        $entity['import_type'] = 'sr_response_keys';
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getResponseKeyService(): SrResponseKeyService
    {
        return $this->responseKeyService;
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
