<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
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
        private ProviderService        $providerService,
        private RateLimitService       $rateLimitService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new S());
        $this->providerService->setThrowException(false);
        $this->rateLimitService->setThrowException(false);
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

    protected function overwrite(array $data, bool $withChildren): array
    {
        return $this->import(ImportAction::OVERWRITE, $data, $withChildren);
    }

    protected function create(array $data, bool $withChildren): array
    {
        return $this->import(ImportAction::CREATE, $data, $withChildren);
    }

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        try {
            if (!empty($data['provider'])) {
                $provider = $data['provider'];
            } elseif (!empty($data['provider_id'])) {
                $provider = $this->providerService->getProviderById((int)$data['provider_id']);
            } else {
                return [
                    'success' => false,
                    'message' => "Provider {$data['name']} is required."
                ];
            }
            if (!$provider instanceof Provider) {
                return [
                    'success' => false,
                    'message' => "Provider {$data['name']} not found."
                ];
            }
            $rateLimit = $provider->providerRateLimit()->first();
            if (
                !$rateLimit &&
                !$this->rateLimitService->createProviderRateLimit($provider, $data)
            ) {
                return [
                    'success' => false,
                    'message' => "Failed to create provider rate limit for provider {$provider->name}."
                ];
            }
            if (!$this->rateLimitService->saveProviderRateLimit($rateLimit, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to update provider rate limit for provider {$provider->name}."
                ];
            }
            return [
                'success' => true,
                'message' => "Sr rate limit for provider {$provider->name} imported successfully."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
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
