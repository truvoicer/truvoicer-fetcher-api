<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Sr;
use App\Models\SrRateLimit;
use App\Services\ApiServices\RateLimitService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Exception;

class SrRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService       $rateLimitService,
        private SrService $srService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrRateLimit());
        $this->srService->setThrowException(false);
        $this->rateLimitService->setThrowException(false);
    }

    protected function loadDependencies(): void
    {
        $this->srService->setThrowException(false);
        $this->rateLimitService->setThrowException(false);
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_RATE_LIMIT->value,
            'Rate Limits',
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import rate limit to provider',
                'dest' => ImportType::PROVIDER->value,
                'required_fields' => ['id'],
            ],
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import rate limit to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map): array
    {
        return $this->create($data, $withChildren, $map);
    }

    protected function create(array $data, bool $withChildren, array $map): array
    {
        try {
            $sr = $this->findSr($data);
            if (!$sr['success']) {
                return $sr;
            }
            $sr = $sr['sr'];
            if (!$this->rateLimitService->createSrRateLimit($sr, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create sr rate limit for Sr {$sr->name}."
                ];
            }
            return [
                'success' => true,
                'message' => "Sr rate limit for Sr {$sr->name} imported successfully."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function findSr(array $data): array
    {
        if (!empty($data['sr'])) {
            $sr = $data['sr'];
        } elseif (!empty($data['sr_id'])) {
            $sr = $this->srService->getServiceRequestById((int)$data['sr_id']);
        } else {
            return [
                'success' => false,
                'message' => "Sr is required for rate limit."
            ];
        }
        if (!$sr instanceof Sr) {
            return [
                'success' => false,
                'message' => "Sr not found for rate limit."
            ];
        }
        return [
            'success' => true,
            'sr' => $sr
        ];
    }


    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data): array
    {
        return [];
    }

    public function validateImportData(array $data): void
    {

    }

    public function filterImportData(array $data): array
    {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => [$this->parseEntity($data)]
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

    public function getRateLimitService(): RateLimitService
    {
        return $this->rateLimitService;
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
                    ImportType::SR_RATE_LIMIT => (!empty($item['sr_rate_limit']))? [$item['sr_rate_limit']] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }

}
