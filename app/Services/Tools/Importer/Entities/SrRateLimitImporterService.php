<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Services\ApiServices\RateLimitService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService       $rateLimitService,
        private SrService $srService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new S());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_RATE_LIMIT->value,
            'Rate Limits',
            null,
            null,
            null,
            [],
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

    public function import(string $action, array $data, bool $withChildren): array
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
        if (!$this->rateLimitService->createSrRateLimit($sr, $data)) {
            return [
                'success' => false,
                'message' => "Failed to create sr rate limit."
            ];
        }
        return [
            'success' => true,
            'message' => "Sr rate limit for Sr {$sr->name} imported successfully."
        ];
    }

    public function importSelfNoChildren(string $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(string $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data)
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
}
