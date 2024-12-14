<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SrImporterService extends ImporterBase
{

    public function __construct(
        private SrConfigImporterService       $srConfigImporterService,
        private SrParameterImporterService    $srParameterImporterService,
        private SrResponseKeysImporterService $srResponseKeysImporterService,
        private SrRateLimitImporterService    $srRateLimitImporterService,
        private SrScheduleImporterService     $srScheduleImporterService,
        private SImporterService              $sImporterService,
        private CategoryImporterService       $categoryImporterService,
        private ApiService                    $apiService,
        protected AccessControlService        $accessControlService
    )
    {
        parent::__construct($accessControlService, new SResponseKey());
    }

    protected function loadDependencies(): void
    {
        $this->providerService->setThrowException(false);
        $this->srService->setThrowException(false);
        $this->apiService->setThrowException(false);
        $this->apiService->setUser($this->getUser());
        $this->sImporterService->setThrowException(false)->setUser($this->getUser());
        $this->srRateLimitImporterService->setThrowException(false)->setUser($this->getUser());
        $this->srScheduleImporterService->setThrowException(false)->setUser($this->getUser());
        $this->srResponseKeysImporterService->setThrowException(false)->setUser($this->getUser());
        $this->srConfigImporterService->setThrowException(false)->setUser($this->getUser());
        $this->categoryImporterService->setThrowException(false)->setUser($this->getUser());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR->value,
            'Srs',
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
                'label' => 'Import sr (no children) to provider',
                'dest' => ImportType::PROVIDER->value,
                'required_fields' => ['id', 'name', 'label'],
            ],
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => ' Import sr (including children) to provider',
                'dest' => ImportType::PROVIDER->value,
                'required_fields' => ['id', 'name', 'label'],
            ],
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {

        try {
            $provider = $this->findProvider(ImportType::SR, $data, $map, $dest);
            if (!$provider['success']) {
                return $provider;
            }
            $provider = $provider['provider'];


            $serviceData = $this->getServiceData($data, $map, $dest);
            if (!$serviceData['success']) {
                return $serviceData;
            }
            $service = $this->getService($serviceData['service']);
            if (!$service['success']) {
                return $service;
            }
            $service = $service['service'];
            $data['service'] = $service->id;

            $category = $this->createCategory($data, $withChildren, $map);
            if ($category) {
                $data['category'] = $category->id;
            }

            $sr = $this->srService->getRequestByName($provider, $data['name']);
            if (!$sr instanceof Sr) {
                return [
                    'success' => false,
                    'message' => "Service Request {$data['name']} not found for {$provider->name}."
                ];
            }

            if (!$this->srService->updateServiceRequest($sr, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to update sr {$data['name']} for {$provider->name}."
                ];
            }
            if ($withChildren) {
                return [
                    'success' => true,
                    'message' => "Service Request {$data['name']} update for {$provider->name}.",
                    'data' => $this->importSrChildren(ImportAction::OVERWRITE, $provider, $sr, $data, true, $map),
                ];
            }
            return [
                'success' => true,
                'message' => "Service Request {$data['name']} update for {$provider->name}."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getService(array $data): array
    {
        $service = $this->apiService->getServiceRepository()->findUserModelBy(new S(), $this->getUser(), [
            ['name', '=', $data['name']]
        ], false);

        if (!$service instanceof S) {
            return [
                'success' => false,
                'message' => "Service {$data['name']} not found."
            ];
        }
        return [
            'success' => true,
            'service' => $service
        ];
    }

    private function getServiceData(array $data, array $map, ?array $dest = null): array
    {
        if (empty($data['s'])) {
            return [
                'success' => false,
                'message' => "Service is required for sr {$data['name']}."
            ];
        }
        if (empty($data['s']['name'])) {
            return [
                'success' => false,
                'message' => "Service name is required for sr {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'service' => $data['s']
        ];
    }
    private function createCategory(array $data, bool $withChildren, array $map): Model|bool{
        if (
            empty($data['category']) ||
            !is_array($data['category'])
        ) {
            return false;
        }
        $category = $this->categoryImporterService->getCategoryService()->getCategoryRepository()->findUserCategoryByName(
            $this->getUser(),
            $data['category']['name']
        );

        if ($category) {
            return $category;
        }
        if (
            !$this->categoryImporterService->import(
                ImportAction::CREATE,
                $data['category'],
                $withChildren,
                $map
            )['success']
        ) {
            return false;
        }
        return $this->categoryImporterService->getCategoryService()->getCategoryRepository()->getModel();
    }
    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $provider = $this->findProvider(ImportType::SR, $data, $map, $dest);
            if (!$provider['success']) {
                return $provider;
            }
            $provider = $provider['provider'];
            $checkSr = $provider->serviceRequest()->where('name', $data['name']);

            if ($checkSr->first() instanceof Sr) {
                $data['label'] = $data['name'] = $this->srService->getServiceRequestRepository()->buildCloneEntityStr(
                    $checkSr,
                    'name',
                    $data['name']
                );
            }
            $serviceData = $this->getServiceData($data, $map, $dest);

            if (!$serviceData['success']) {
                return $serviceData;
            }

            $service = $this->sImporterService->create($serviceData['service'], $withChildren, $map);
            if (!$service['success']) {
                return $service;
            }

            $service = $this->sImporterService->getApiService()->getServiceRepository()->getModel();
            $data['service'] = $service->id;

            $category = $this->createCategory($data, $withChildren, $map);

            if ($category) {
                $data['category'] = $category->id;
            }
            if (!empty($extraData['isChildSr'])) {
                if (empty($extraData['sr']) || !$extraData['sr'] instanceof Sr) {
                    return [
                        'success' => false,
                        'message' => "Parent Sr is required for child"
                    ];
                }

                if (!$this->srService->createChildSr($provider, $extraData['sr'], $data)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create child sr."
                    ];
                }
            } else {
                if (!$this->srService->createServiceRequest($provider, $data)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create provider."
                    ];
                }
            }
            $sr = $this->srService->getServiceRequestRepository()->getModel();
            if ($withChildren) {
                return [
                    'success' => true,
                    'message' => "Service Request {$data['name']} created for {$provider->name}.",
                    'data' => $this->importSrChildren(ImportAction::CREATE, $provider, $sr, $data, true, $map),
                ];
            }
            return [
                'success' => true,
                'message' => "Service Request {$data['name']} created for {$provider->name}."
            ];
        } catch
        (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function importSrChildren(ImportAction $action, Provider $provider, Sr $sr, array $data, bool $withChildren, array $map): array
    {
        $response = [];
        $data['sr'] = $sr;
        if (
            !empty($data['sr_rate_limit']) &&
            is_array($data['sr_rate_limit'])
        ) {
            $data['sr_rate_limit']['sr'] = $sr;
            $response[] = $this->srRateLimitImporterService->import(
                $action,
                $data['sr_rate_limit'],
                $withChildren,
                $map
            );
        }
        if (
            !empty($data['sr_schedule']) &&
            is_array($data['sr_schedule'])
        ) {
            $data['sr_schedule']['sr'] = $sr;
            $response[] = $this->srScheduleImporterService->import(
                $action,
                $data['sr_schedule'],
                $withChildren,
                $map
            );
        }
        if (
            !empty($data['sr_response_keys']) &&
            is_array($data['sr_response_keys'])
        ) {
            $response = array_merge(
                $response,
                $this->srResponseKeysImporterService->batchImport(
                    $action,
                    array_map(function ($parameter) use ($sr) {
                        $parameter['sr'] = $sr;
                        return $parameter;
                    }, $data['sr_response_keys']),
                    $withChildren,
                    $map
                )
            );
        }

        if (
            !empty($data['sr_parameter']) &&
            is_array($data['sr_parameter'])
        ) {
            $response = array_merge(
                $response,
                $this->srParameterImporterService->batchImport(
                    $action,
                    array_map(function ($parameter) use ($sr) {
                        $parameter['sr'] = $sr;
                        return $parameter;
                    }, $data['sr_parameter']),
                    $withChildren,
                    $map
                )
            );
        }
        if (
            !empty($data['sr_config']) &&
            is_array($data['sr_config'])
        ) {
            $response = array_merge(
                $response,
                $this->srConfigImporterService->batchImport(
                    $action,
                    array_map(function ($config) use ($sr) {
                        $config['sr'] = $sr;
                        return $config;
                    }, $data['sr_config']),
                    $withChildren,
                    $map
                )
            );
        }
        if (
            !empty($data['child_srs']) &&
            is_array($data['child_srs'])
        ) {
            $response = array_merge(
                $response,
                $this->batchImport(
                    $action,
                    array_map(function ($childSr) use ($sr, $provider) {
                        $childSr['sr'] = $sr;
                        $childSr['provider'] = $provider;
                        return $childSr;
                    }, $data['child_srs']),
                    $withChildren,
                    $map,
                    ['isChildSr' => true, 'sr' => $sr]
                )
            );
        }
        return [
            'success' => true,
            'data' => $response,
        ];
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        return $this->importSelf($action, $map, $data, false, $dest);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {

        return $this->importSelf($action, $map, $data, true, $dest);
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
                !empty($sr['sr_rate_limit']) &&
                is_array($sr['sr_rate_limit'])
            ) {
                $this->srRateLimitImporterService->validateImportData($sr['sr_rate_limit']);
            }
            if (
                !empty($sr['sr_schedule']) &&
                is_array($sr['sr_schedule'])
            ) {
                $this->srScheduleImporterService->validateImportData($sr['sr_schedule']);
            }
            if (
                !empty($sr['sr_response_keys']) &&
                is_array($sr['sr_response_keys'])
            ) {
                $this->srResponseKeysImporterService->validateImportData($sr['sr_response_keys']);
            }
            if (
                !empty($sr['sr_parameter']) &&
                is_array($sr['sr_parameter'])
            ) {
                $this->srParameterImporterService->validateImportData($sr['sr_parameter']);
            }
            if (
                !empty($sr['sr_config']) &&
                is_array($sr['sr_config'])
            ) {
                $this->srConfigImporterService->validateImportData($sr['sr_config']);
            }
            if (
                !empty($sr['child_srs']) &&
                is_array($sr['child_srs'])
            ) {
                $this->validateImportData($sr['child_srs']);
            }
        }
    }

    public function filterData(array $data): array
    {
        $filterSrs = array_filter($data, function ($sr) {
            return (
                !empty($sr['name']) &&
                !empty($sr['label'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return array_map(function ($sr) {
            if (
                !empty($sr['child_srs']) &&
                is_array($sr['child_srs'])
            ) {
                $sr['child_srs'] = $this->filterData($sr['child_srs']);
            }
            return $sr;
        }, $filterSrs);
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

    public function parseEntity(array $entity): array
    {

        $entity['children'] = [];
        if (
            !empty($entity['sr_rate_limit']) &&
            is_array($entity['sr_rate_limit'])
        ) {
            $entity['children'][] = $this->srRateLimitImporterService->filterImportData($entity['sr_rate_limit']);
        }
        if (
            !empty($entity['sr_schedule']) &&
            is_array($entity['sr_schedule'])
        ) {
            $entity['children'][] = $this->srScheduleImporterService->filterImportData($entity['sr_schedule']);
        }
        if (
            !empty($entity['sr_response_keys']) &&
            is_array($entity['sr_response_keys'])
        ) {
            $entity['children'][] = $this->srResponseKeysImporterService->filterImportData($entity['sr_response_keys']);
        }
        if (
            !empty($entity['sr_parameter']) &&
            is_array($entity['sr_parameter'])
        ) {
            $entity['children'][] = $this->srParameterImporterService->filterImportData($entity['sr_parameter']);
        }
        if (
            !empty($entity['sr_config']) &&
            is_array($entity['sr_config'])
        ) {
            $entity['children'][] = $this->srConfigImporterService->filterImportData($entity['sr_config']);
        }
        if (
            !empty($entity['child_srs']) &&
            is_array($entity['child_srs'])
        ) {
            $entity['children'][] = $this->filterImportData($entity['child_srs']);
        }
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }


    public function getSrService(): SrService
    {
        return $this->srService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function getExportTypeData($item): array|bool
    {
        return [];
    }

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['srs', 'sr', 'child_srs'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::SR => [$item],
                    ImportType::SR_RESPONSE_KEY => (!empty($item['sr_response_keys']))? $item['sr_response_keys'] : [],
                    ImportType::SR_CONFIG => (!empty($item['sr_config']))? $item['sr_config'] : [],
                    ImportType::SR_PARAMETER => (!empty($item['sr_parameter']))? $item['sr_parameter'] : [],
                    ImportType::SR_RATE_LIMIT => (!empty($item['sr_rate_limit']))? [$item['sr_rate_limit']] : [],
                    ImportType::SR_SCHEDULE => (!empty($item['sr_schedule']))? [$item['sr_schedule']] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }

}
