<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Category;
use App\Models\Provider;
use App\Repositories\SrRepository;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\IExport\IExportTypeService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProviderImporterService extends ImporterBase
{
    private SrRepository $srRepository;

    public function __construct(
        private SrImporterService                 $srImporterService,
        private ProviderPropertiesImporterService $providerPropertiesImporterService,
        private ProviderRateLimitImporterService  $providerRateLimitImporterService,
        private CategoryImporterService           $categoryImporterService,
        protected AccessControlService            $accessControlService
    )
    {
        parent::__construct($accessControlService, new Provider());
        $this->srRepository = new SrRepository();
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            true,
            'id',
            ImportType::PROVIDER->value,
            'Providers',
            'name',
            'label',
            'label',
            ['children'],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import provider (no children)',
                'dest' => null,
                'required_fields' => ['id', 'name', 'label'],
            ],
            [
                'name' => ImportMappingType::SELF_WITH_CHILDREN->value,
                'label' => 'Import provider (including children)',
                'dest' => null,
                'required_fields' => ['id', 'name', 'label'],
            ],
        ];
    }

    protected function loadDependencies(): void
    {
        $this->providerService->setThrowException(false);
        $this->providerPropertiesImporterService->setUser($this->getUser());
        $this->providerRateLimitImporterService->setUser($this->getUser());
        $this->categoryImporterService->setUser($this->getUser());
        $this->srImporterService->setUser($this->getUser());
    }

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        $provider = $this->providerService->getProviderRepository()->findByName(
            $data['name']
        );
        if (!$provider instanceof Provider) {
            return [
                'success' => false,
                'message' => "Provider {$data['name']} not found."
            ];
        }
        if (!$this->entityService->lockEntity($this->getUser(), $provider->id, Provider::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock provider {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider import is locked.'
        ];
    }
    public function unlock(Provider $provider): array
    {
        if (!$this->entityService->lockEntity($this->getUser(), $provider->id, Provider::class)) {
            return [
                'success' => false,
                'message' => "Failed to unlock provider {$provider->name}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Provider import is unlocked.'
        ];
    }

    private function importChildren(ImportAction $action, Provider $provider, array $data, array $map)
    {
        $response = [];
        if (
            !empty($data['properties']) &&
            is_array($data['properties'])
        ) {
            $response = array_merge(
                $response,
                $this->providerPropertiesImporterService->batchImport(
                    $action,
                    array_map(function ($property) use ($provider) {
                        $property['provider'] = $provider;
                        return $property;
                    }, $data['properties']),
                    true,
                    $map
                )
            );
        }
        if (
            !empty($data['provider_rate_limit']) &&
            is_array($data['provider_rate_limit'])
        ) {
            $data['provider_rate_limit']['provider'] = $provider;
            $response[] = $this->providerRateLimitImporterService->import(
                $action,
                $data['provider_rate_limit'],
                true,
                $map
            );
        }

        if (
            !empty($data['categories']) &&
            is_array($data['categories'])
        ) {
            $response = array_merge(
                $response,
                $this->categoryImporterService->batchImport(
                    $action,
                        array_map(function ($category) use ($provider) {
                            $category['provider'] = $provider;
                            return $category;
                        }, $data['categories']),
                    true,
                    $map
                )
            );
        }
        if (
            !empty($data['srs']) &&
            is_array($data['srs'])
        ) {
            $response = array_merge(
                $response,
                $this->srImporterService->batchImport(
                    $action,
                    array_map(function ($sr) use ($provider) {
                        $sr['provider'] = $provider;
                        return $sr;
                    }, $data['srs']),
                    true,
                    $map
                )
            );
        }
        return $response;
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $checkProvider = $this->providerService->getProviderRepository()->findUserModelQuery(
                new Provider(),
                $this->getUser(),
                [['name', '=', $data['name']]],
                false
            );
            if ($checkProvider->first() instanceof Provider) {
                $data['label'] = $data['name'] = $this->providerService->getProviderRepository()->buildCloneEntityStr(
                    $checkProvider,
                    'name',
                    $data['name']
                );
            }

            if (
                !empty($data['categories']) &&
                is_array($data['categories'])
            ) {
                $data['categories'] = UtilHelpers::arrayExceptKey($data['categories'], ['id'], true);
            }
            if (!$this->providerService->createProvider($this->getUser(), $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create provider {$data['name']}."
                ];
            }

            $provider = $this->providerService->getProviderRepository()->getModel();

            if ($withChildren) {
                $response = $this->importChildren(
                    ImportAction::CREATE,
                    $provider,
                    $data,
                    $map
                );
            } else {
                $response = [
                    'success' => true,
                    'message' => "Provider {$data['name']} imported successfully. No children imported."
                ];
            }
            $unlockProvider = $this->unlock($provider);
            if (!$unlockProvider['success']) {
                return $unlockProvider;
            }
            return [
                'success' => true,
                'message' => sprintf(
                    "Provider imported successfully | name: %s | label: %s with_children: %s",
                    $provider->name,
                    $provider->label,
                    ($withChildren) ? 'true' : 'false'
                ),
                'data' => $response,
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
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $checkProvider = $this->providerService->getProviderRepository()->findUserModelQuery(
                new Provider(),
                $this->getUser(),
                [['name', '=', $data['name']]],
                false
            );
            if (!$checkProvider->first() instanceof Provider) {
                return [
                    'success' => false,
                    'message' => "Provider {$data['name']} not found."
                ];
            }
            if (!$this->providerService->updateProvider($this->getUser(), $checkProvider, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to update provider {$data['name']}."
                ];
            }
            $provider = $this->providerService->getProviderRepository()->getModel();
            if ($withChildren) {
                $response = $this->importChildren(
                    ImportAction::OVERWRITE,
                    $provider,
                    $data,
                    $map
                );
            } else {
                $response = [
                    'success' => true,
                    'message' => sprintf(
                        "Provider imported successfully | name: %s | label: %s with_children: false",
                        $provider->name,
                        $provider->label,
                    ),
                ];
            }
            $unlockProvider = $this->unlock($provider);
            if (!$unlockProvider['success']) {
                return $unlockProvider;
            }
            return [
                'success' => true,
                'data' => $response,
                'message' => sprintf(
                    "Provider imported successfully | name: %s | label: %s with_children: %s",
                    $provider->name,
                    $provider->label,
                    ($withChildren) ? 'true' : 'false'
                ),
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
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'error' => $e->getMessage(),
            ];
        }
    }

    public function validateImportData(array $data): void
    {
        foreach ($data as $provider) {
            if (empty($provider['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Provider name is required."
                );
            }
            if (empty($provider['label'])) {
                $this->addError(
                    'import_type_validation',
                    "Provider label is required."
                );
            }
            if (
                !empty($data['srs']) &&
                is_array($data['srs'])
            ) {
                $this->srImporterService->validateImportData($data['srs']);
            }
        }
    }

    public function filterData(array $data): array
    {
        $filterProviders = array_filter($data, function ($provider) {
            return (
                !empty($provider['name']) &&
                !empty($provider['label'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return array_map(function ($provider) {
            if (
                !empty($provider['srs']) &&
                is_array($provider['srs'])
            ) {
                $provider['srs'] = $this->srImporterService->filterData($provider['srs']);
            }
            return $provider;
        }, $filterProviders);
    }

    public function filterImportData(array $data): array
    {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch(
                $this->filterData($data)
            )
        ];
    }

    public function getExportData(): array
    {
        return $this->providerService->findProviders(
            $this->getUser()
        )->toArray();
    }

    private function getSrWith()
    {
        return [
            'srConfig' => function ($query) {
                $query->with('property');
            },
            'srParameter',
            'srSchedule',
            'category',
            'srRateLimit',
            's' => function ($query) {
                $query->with([
                    'sResponseKeys' => function ($query) {
                        $query->with(['srResponseKey']);
                    }
                ]);
            },
        ];
    }

    public function getExportTypeData($item): array|bool
    {
        $srs = (!empty($item["srs"]) && is_array($item["srs"])) ?
            $item["srs"] : [];
        $this->providerService->getProviderRepository()
            ->setWith([
                'srs' => function ($query) use ($srs) {
                    if (empty($srs)) {
                        $query->with($this->getSrWith());
                        return;
                    }
                    $query->whereIn('id', array_column($srs, 'id'));
                    $childSrs = [];
                    foreach ($srs as $sr) {
                        if (is_array($sr['child_srs'])) {
                            $childSrs = array_merge($childSrs, $sr['child_srs']);
                        }
                    }
                    $query = $this->srRepository->buildNestedSrQuery(
                        $query,
                        $childSrs,
                        $this->getSrWith()
                    );
                },
                'categories',
                'properties',
                'providerRateLimit'
            ]);
        $provider = $this->providerService->getProviderRepository()->findById(
            $item["id"],
        );
        if (!$provider) {
            return false;
        }

        if ($this->accessControlService->inAdminGroup()) {
            return $provider->toArray();
        }

        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            $provider,
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ],
            false
        );
        return $isPermitted ? $provider->toArray() : false;

    }

    public function parseEntity(array $entity): array
    {
        $entity['children'] = [];
        if (
            !empty($entity['srs']) &&
            is_array($entity['srs'])
        ) {
            $entity['children'][] = $this->srImporterService->filterImportData($entity['srs']);
        }
        if (
            !empty($entity['properties']) &&
            is_array($entity['properties'])
        ) {
            $entity['children'][] = $this->providerPropertiesImporterService->filterImportData($entity['properties']);
        }
        if (
            !empty($entity['provider_rate_limit']) &&
            is_array($entity['provider_rate_limit'])
        ) {
            $entity['children'][] = $this->providerRateLimitImporterService->filterImportData($entity['provider_rate_limit']);
        }
        if (
            !empty($entity['categories']) &&
            is_array($entity['categories'])
        ) {
            $entity['children'][] = $this->categoryImporterService->filterImportData($entity['categories']);
        }
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function ($providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null
    {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: [],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::PROVIDER => [$item],
                    default => [],
                };
            },
            operation: $operation
        );
    }

    public function getProviderService(): ProviderService
    {
        return $this->providerService;
    }
}
