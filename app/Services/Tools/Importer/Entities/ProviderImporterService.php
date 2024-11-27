<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Repositories\SrRepository;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderService;
use App\Services\Permission\AccessControlService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProviderImporterService extends ImporterBase
{
    private SrRepository $srRepository;

    public function __construct(
        private ProviderService                   $providerService,
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

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        dd($action);
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
            if (!$this->providerService->createProvider($this->getUser(), $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create provider {$data['name']}."
                ];
            }
            $response = [];
            $data['provider'] = $this->providerService->getProviderRepository()->getModel();
            if (
                !empty($data['srs']) &&
                is_array($data['srs'])
            ) {
                $response = array_merge(
                    $response,
                    $this->srImporterService->batchImport(
                        $action,
                        $data['srs'],
                        $withChildren
                    )
                );
            }
            if (
                !empty($data['properties']) &&
                is_array($data['properties'])
            ) {
                $response = array_merge(
                    $response,
                    $this->providerPropertiesImporterService->batchImport(
                        $action,
                        $data['properties'],
                        $withChildren
                    )
                );
            }
            if (
                !empty($data['provider_rate_limit']) &&
                is_array($data['provider_rate_limit'])
            ) {
                $response[] = $this->providerRateLimitImporterService->import(
                    $action,
                    $data['provider_rate_limit'],
                    $withChildren
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
                        $data['categories'],
                        $withChildren
                    )
                );
            }
            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
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

    public function getExportTypeData($item): array|bool
    {
        $srs = (!empty($item["srs"]) && is_array($item["srs"])) ?
            $item["srs"] : [];
        $this->providerService->getProviderRepository()->setWith([
            'srs' => function ($query) use ($srs) {
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
                    [
                        'srConfig' => function ($query) {
                            $query->with('property');
                        },
                        'srParameter',
                        'srSchedule',
                        'srRateLimit',
                        'srResponseKeys',
                        's' => function ($query) {
                            $query->with('sResponseKeys');
                        },
                        'category'
                    ]
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

    public function getProviderService(): ProviderService
    {
        return $this->providerService;
    }
}
