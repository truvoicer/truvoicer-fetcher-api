<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Models\S;
use App\Services\ApiServices\RateLimitService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Model;

class ProviderRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private ProviderService $providerService,
        private RateLimitService       $rateLimitService,
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
            ImportType::PROVIDER_RATE_LIMIT->value,
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
        if (!empty($data['provider'])) {
            $provider = $data['provider'];
        } elseif (!empty($data['provider_id'])) {
            $provider = $this->providerService->getProviderById((int)$data['provider_id']);
        } else {
            return [
                'success' => false,
                'message' => "Provider is required."
            ];
        }
        if (!$provider instanceof Provider) {
            return [
                'success' => false,
                'message' => "Provider not found."
            ];
        }
        if (!$this->rateLimitService->createProviderRateLimit($provider, $data)) {
            return [
                'success' => false,
                'message' => "Failed to create provider rate limit."
            ];
        }
        return [
            'success' => true,
            'message' => "Sr rate limit for provider {$provider->name} imported successfully."
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
