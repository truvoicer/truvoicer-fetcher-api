<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;

class SrResponseKeysImporterService extends ImporterBase
{

    public function __construct(
        private SResponseKeysImporterService   $sResponseKeysImporterService,
        private SrResponseKeyService   $srResponseKeyService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrResponseKey());
    }

    protected function loadDependencies(): void
    {
        $this->srService->setThrowException(false);
        $this->srResponseKeyService->setThrowException(false);
        $this->sResponseKeysImporterService->setThrowException(false)->setUser($this->getUser());
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

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        $sr = $this->findSr(ImportType::SR_RESPONSE_KEY, $data, $map, $dest);
        if (!$sr['success']) {
            return $sr;
        }
        $sr = $sr['sr'];

        $srResponseKey = $sr->srResponseKeys()->where('name', $data['name'])->first();
        if (!$srResponseKey instanceof SrResponseKey) {
            return [
                'success' => false,
                'message' => "Sr response key not found for Sr {$sr->name}"
            ];
        }
        if (!$this->entityService->lockEntity($this->getUser(), $srResponseKey->id, SrResponseKey::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock sr response key {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Sr response key import is locked.'
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        $sr = $this->findSr(ImportType::SR_RESPONSE_KEY, $data, $map, $dest);
        if (!$sr['success']) {
            return $sr;
        }
        $sr = $sr['sr'];

        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => "S response key name is required for sr {$sr->name}."
            ];
        }
        $this->sResponseKeysImporterService->getSResponseKeyService()->getResponseKeyRepository()->addWhere(
            'name',
            $data['name']
        );
        $responseKey = $this->sResponseKeysImporterService->getSResponseKeyService()->getResponseKeyRepository()->findOne();
        if (!$responseKey instanceof SResponseKey) {
            return [
                'success' => false,
                'message' => "S response key {$data['name']} not found for sr {$sr->name}."
            ];
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

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
       $sr = $this->findSr(ImportType::SR_RESPONSE_KEY, $data, $map, $dest);
        if (!$sr['success']) {
            return $sr;
        }
        $sr = $sr['sr'];

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
            $responseKey = $this->sResponseKeysImporterService->create(
                $data,
                $withChildren,
                $map
            );
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

    public function getImportMappings(array $data): array
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

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {

        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['srs', 'sr', 'child_srs'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::SR_RESPONSE_KEY => (!empty($item['sr_response_keys']))? $item['sr_response_keys'] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }

}
