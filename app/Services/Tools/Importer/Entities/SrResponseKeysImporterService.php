<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrResponseKeysImporterService extends ImporterBase
{

    public function __construct(
        private SResponseKeysImporterService   $sResponseKeysImporterService,
        private SrResponseKeyService   $srResponseKeyService,
        private SrService              $srService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrResponseKey());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_RESPONSE_KEY->value,
            'Sr Response Keys',
            'name',
            '{name}: {pivot.value}',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr response key to sr (no children)',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
            [
                'name' => ImportMappingType::SELF_WITH_CHILDREN->value,
                'label' => 'Import sr response key to sr (including children)',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        if (!empty($data['sr'])) {
            $sr = $data['sr'];
        } elseif (!empty($data['sr_id'])) {
            $sr = $this->srService->getServiceRequestById((int)$data['sr_id']);
        } else {
            return [
                'success' => false,
                'message' => "Sr is required."
            ];
        }
        if (!$sr instanceof Sr) {
            return [
                'success' => false,
                'message' => "Sr not found."
            ];
        }

        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => "S response key name is required."
            ];
        }
        $this->sResponseKeysImporterService->getSResponseKeyService()->getResponseKeyRepository()->addWhere(
            'name',
            $data['name']
        );
        $responseKey = $this->sResponseKeysImporterService->getSResponseKeyService()->getResponseKeyRepository()->findOne();
        if (!$responseKey instanceof SResponseKey) {
            $responseKey = $this->sResponseKeysImporterService->import(
                $action,$data, false);
            if (!$responseKey['success']) {
                return $responseKey;
            }
            $responseKey = $this->sResponseKeysImporterService->getSResponseKeyService()->getResponseKeyRepository()->getModel();
        }
        if (
            !$this->srResponseKeyService->createSrResponseKey(
                $this->getUser(),
                $sr,
                $responseKey->name,
                $data
            )
        ) {
            return [
                'success' => false,
                'message' => "Failed to create sr response key."
            ];
        }
        return [
            'success' => true,
            'message' => "Sr response key({$responseKey->name}) for Sr {$sr->name} imported successfully."
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

    public function getSrResponseKeyService(): SrResponseKeyService
    {
        return $this->srResponseKeyService;
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
