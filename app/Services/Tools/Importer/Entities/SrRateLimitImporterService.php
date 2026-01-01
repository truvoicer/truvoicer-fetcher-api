<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use Truvoicer\TfDbReadCore\Helpers\Tools\UtilHelpers;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\SrRateLimit;
use Truvoicer\TfDbReadCore\Services\ApiServices\RateLimitService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use Exception;

class SrRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService       $rateLimitService,
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

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        $sr = $this->findSr(ImportType::SR_RATE_LIMIT, $data, $map, $dest);
        if (!$sr['success']) {
            return $sr;
        }
        $sr = $sr['sr'];
        $srRateLimit = $sr->srRateLimit()->first();

        if (!$srRateLimit instanceof SrRateLimit) {
            return [
                'success' => false,
                'message' => "Sr rate limit not found for Sr {$sr->name}"
            ];
        }
        if (!$this->entityService->lockEntity($this->getUser(), $srRateLimit->id, SrRateLimit::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock sr rate limit {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Sr rate limit import is locked.'
        ];
    }

    public function unlock(SrRateLimit $srRateLimit): array
    {
        if (!$this->entityService->unlockEntity($this->getUser(), $srRateLimit->id, SrRateLimit::class)) {
            return [
                'success' => false,
                'message' => "Failed to unlock sr rate limit."
            ];
        }
        return [
            'success' => true,
            'message' => 'Sr rate limit import is unlocked.'
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        return $this->create($data, $withChildren, $map);
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $sr = $this->findSr(ImportType::SR_RATE_LIMIT, $data, $map, $dest);
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
            $unlockSrRateLimit = $this->unlock($this->rateLimitService->getSrRateLimitRepository()->getModel());
            if (!$unlockSrRateLimit['success']) {
                return $unlockSrRateLimit;
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
