<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use Truvoicer\TfDbReadCore\Helpers\Tools\UtilHelpers;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\ProviderRateLimit;
use Truvoicer\TfDbReadCore\Services\ApiServices\RateLimitService;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use App\Services\Tools\IExport\IExportTypeService;
use Exception;
use Illuminate\Support\Facades\Log;

class ProviderRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService       $rateLimitService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new ProviderRateLimit());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::PROVIDER_RATE_LIMIT->value,
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

    protected function loadDependencies(): void
    {
        $this->providerService->setThrowException(false);
        $this->rateLimitService->setThrowException(false);
        $this->rateLimitService->setUser($this->getUser());
    }

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        $provider = $this->findProvider(ImportType::PROVIDER, $data, $map, $dest);
        if (!$provider['success']) {
            return $provider;
        }
        $provider = $provider['provider'];

        $rateLimit = $provider->providerRateLimit()->first();
        if (!$rateLimit) {
            return [
                'success' => false,
                'message' => "Failed to find provider rate limit for provider {$provider->name}."
            ];
        }
        if (!$this->entityService->lockEntity($this->getUser(), $rateLimit->id, ProviderRateLimit::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock provider provider {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider property import is locked.'
        ];
    }

    public function unlock(ProviderRateLimit $providerRateLimit): array
    {
        if (!$this->entityService->unlockEntity($this->getUser(), $providerRateLimit->id, ProviderRateLimit::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock provider rate limit {$providerRateLimit->name}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider rate limit import is locked.'
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        return $this->import(ImportAction::OVERWRITE, $data, $withChildren, $map);
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        return $this->import(ImportAction::CREATE, $data, $withChildren, $map);
    }

    public function import(ImportAction $action, array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = [], ?bool $lock = false): array
    {
        try {
            $provider = $this->findProvider(ImportType::PROVIDER, $data, $map, $dest);
            if (!$provider['success']) {
                return $provider;
            }

            $provider = $provider['provider'];

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
            $rateLimit = $this->rateLimitService->getProviderRateLimitRepository()->getModel();
            if (!$this->rateLimitService->saveProviderRateLimit($rateLimit, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to update provider rate limit for provider {$provider->name}."
                ];
            }
            $unlock = $this->unlock($rateLimit);
            if (!$unlock['success']) {
                return $unlock;
            }
            return [
                'success' => true,
                'message' => "Sr rate limit for provider {$provider->name} imported successfully."
            ];
        } catch (Exception $e) {
            Log::channel(IExportTypeService::LOGGING_NAME)->error(
                $e->getMessage(),
                [
                    'data' => $data
                ]
            );
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

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['properties', 'provider_rate_limit'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::PROVIDER_RATE_LIMIT => (!empty($item['provider_rate_limit']))? [$item['provider_rate_limit']] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }
    public function getExportTypeData($item): array|bool
    {
        return false;
    }
}
