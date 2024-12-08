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

    protected function loadDependencies(): void
    {
        $this->providerService->setThrowException(false);
        $this->providerPropertiesImporterService->setUser($this->getUser());
        $this->providerRateLimitImporterService->setUser($this->getUser());
        $this->categoryImporterService->setUser($this->getUser());
        $this->srImporterService->setUser($this->getUser());
    }


    private function importChildren(ImportAction $action, Provider $provider, array $data)
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
                        $property['provider_id'] = $provider->id;
                        return $property;
                    }, $data['properties']),
                    true
                )
            );
        }
        if (
            !empty($data['provider_rate_limit']) &&
            is_array($data['provider_rate_limit'])
        ) {
            $data['provider_rate_limit']['provider'] = $provider;
            $data['provider_rate_limit']['provider_id'] = $provider->id;
            $response[] = $this->providerRateLimitImporterService->import(
                $action,
                $data['provider_rate_limit'],
                true
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
                        $category['provider_id'] = $provider->id;
                        return $category;
                    }, $data['categories']),
                    true
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
                        $sr['provider_id'] = $provider->id;
                        return $sr;
                    }, $data['srs']),
                    true
                )
            );
        }
        return $response;
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
//            dd((new Provider)->newFromBuilder($data));
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

            if ($withChildren) {
                $response = $this->importChildren(
                    ImportAction::CREATE,
                    $this->providerService->getProviderRepository()->getModel(),
                    $data
                );
            } else {
                $response = [
                    'success' => true,
                    'message' => "Provider {$data['name']} imported successfully. No children imported."
                ];
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

    protected function overwrite(array $data, bool $withChildren): array
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
            if (!$this->providerService->updateProvider($this->getUser(), $checkProvider,  $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to update provider {$data['name']}."
                ];
            }
            if ($withChildren) {
                $response = $this->importChildren(
                    ImportAction::OVERWRITE,
                    $this->providerService->getProviderRepository()->getModel(),
                    $data
                );
            } else {
                $response = [
                    'success' => true,
                    'message' => "Provider {$data['name']} imported successfully. No children imported."
                ];
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
                if (empty($srs)) {
                    $query->with([
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
                    ]);
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

    public function findSr(ImportType $importType, array $providers, array $conditions): array|null {
        foreach ($providers as $item) {
            $matchItem = null;
            switch ($importType) {
                case ImportType::SR:
                    $matchItem = [$item];
                    break;
                case ImportType::SR_RESPONSE_KEY:
                    if (!empty($item['sr_response_keys'])) {
                        $matchItem = $item['sr_response_keys'];
                    }
                    break;
                case ImportType::SR_CONFIG:
                    if (!empty($item['sr_config'])) {
                        $matchItem = $item['sr_config'];
                    }
                    break;
                case ImportType::SR_PARAMETER:
                    if (!empty($item['sr_parameter'])) {
                        $matchItem = $item['sr_parameter'];
                    }
                    break;
                case ImportType::SR_RATE_LIMIT:
                    if (!empty($item['sr_rate_limit'])) {
                        $matchItem = [$item['sr_rate_limit']];
                    }
                    break;
                case ImportType::SR_SCHEDULE:
                    if (!empty($item['sr_schedule'])) {
                        $matchItem = [$item['sr_schedule']];
                    }
                    break;
            }
            $matches = array_filter($conditions, function ($condition, $key) use ($matchItem) {
                return $matchItem[$key] === $condition;
            }, ARRAY_FILTER_USE_BOTH);
            if (count($matches) === count($conditions)) {
                return $item;
            }
            if (!empty($item['child_srs']) && is_array($item['child_srs'])) {
                $value = $this->findSr($importType, $item['child_srs'], [], $conditions);
                if (!empty($value)) {
                    return $value;
                }
            }
            foreach (['srs', 'sr'] as $childrenKey) {
                if (empty($item[$childrenKey]) || !is_array($item[$childrenKey])) {
                    continue;
                }
                $value = $this->findSr($importType, $item[$childrenKey], ['srs', 'sr'], $conditions);
                if (!empty($value)) {
                    return $value;
                }
            }
        }
        return null;
    }

    public function getProviderService(): ProviderService
    {
        return $this->providerService;
    }
}
