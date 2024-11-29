<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SResponseKeysImporterService extends ImporterBase
{

    public function __construct(
        private ApiService             $apiService,
        private SResponseKeysService   $sResponseKeysService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SResponseKey());
        $this->apiService->setThrowException(false);
        $this->sResponseKeysService->setThrowException(false);
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_RESPONSE_KEY->value,
            'S Response Keys',
            'name',
            'name',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import s response key to sr (no children)',
                'dest' => ImportType::SR_RESPONSE_KEY->value,
                'required_fields' => ['id'],
            ],
            [
                'name' => ImportMappingType::SELF_WITH_CHILDREN->value,
                'label' => 'Import s response key to sr (including children)',
                'dest' => ImportType::SR_RESPONSE_KEY->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    protected function overwrite(array $data, bool $withChildren): array
    {
        try {
            $service = $this->findService($data, $withChildren);
            if (!$service['success']) {
                return $service;
            }

            $service = $service['service'];
            $responseKey = $this->sResponseKeysService->getServiceResponseKeyByName(
                $service,
                $data['name']
            );
            if (!$responseKey) {
                return [
                    'success' => false,
                    'message' => "Service response key ({$data['name']}) not found for Sr {$service->name}."
                ];
            }
            if (
                !$this->sResponseKeysService->updateServiceResponseKeys(
                    $responseKey,
                    $data
                )
            ) {
                return [
                    'success' => false,
                    'message' => "Failed to update service response key ({$data['name']}) for Sr {$service->name}."
                ];
            }
            return [
                'success' => true,
                'message' => "Service response key({$data['name']}) for Sr {$service->name} imported successfully."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
            $service = $this->findService($data, $withChildren);
            if (!$service['success']) {
                return $service;
            }
            $service = $service['service'];
            $responseKey = $this->sResponseKeysService->getServiceResponseKeyByName(
                $service,
                $data['name']
            );
            if ($responseKey) {
                return [
                    'success' => false,
                    'message' => "Service response key ({$data['name']}) already exists for Sr {$service->name}."
                ];
            }
            if (
                !$this->sResponseKeysService->createServiceResponseKeys(
                    $service,
                    $data
                )
            ) {
                return [
                    'success' => false,
                    'message' => "Failed to create service response key ({$data['name']}) for Sr {$service->name}."
                ];
            }
            return [
                'success' => true,
                'message' => "Service response key({$data['name']}) for Sr {$service->name} imported successfully."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function findService(array $data): array
    {
        if (!empty($data['service'])) {
            $service = $data['service'];
        } elseif (!empty($data['s_id'])) {
            $service = $this->apiService->getServiceById((int)$data['s_id']);
        } else {
            return [
                'success' => false,
                'message' => "Service is required for response key {$data['name']}."
            ];
        }
        if (!$service instanceof S) {
            return [
                'success' => false,
                'message' => "Service not found for response key {$data['name']}"
            ];
        }
        return [
            'success' => true,
            'service' => $service
        ];
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }

    public function validateImportData(array $data): void
    {
        foreach ($data as $sr) {
            if (empty($sr['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Service name is required."
                );
            }
        }
    }

    public function filterImportData(array $data): array
    {
        $filter = array_filter($data, function ($sr) {
            return $sr;
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
        ];
    }

    public function parseEntity(array $entity): array
    {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getSResponseKeyService(): SResponseKeysService
    {
        return $this->sResponseKeysService;
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
