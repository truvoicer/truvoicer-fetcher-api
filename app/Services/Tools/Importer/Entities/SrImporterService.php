<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\RateLimitService;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Model;

class SrImporterService extends ImporterBase
{

    public function __construct(
        private ProviderService $providerService,
        private SrService $srService,
        private SrConfigImporterService $srConfigImporterService,
        private SrParameterImporterService $srParameterImporterService,
        private SrResponseKeysImporterService $srResponseKeysImporterService,
        private SrRateLimitImporterService $srRateLimitImporterService,
        private SrScheduleImporterService $srScheduleImporterService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new S());
        $this->providerService->setThrowException(false);
        $this->srService->setThrowException(false);
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

    protected function overwrite(array $data, bool $withChildren): array
    {
        try {
            $provider = $this->findProvider($data);
            if (!$provider['success']) {
                return $provider;
            }
            $provider = $provider['provider'];
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
                    'data' => $this->importSrChildren(ImportAction::OVERWRITE, $sr, $data, true),
                ];
            }
            return [
                'success' => true,
                'message' => "Service Request {$data['name']} update for {$provider->name}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
            $provider = $this->findProvider($data);
            if (!$provider['success']) {
                return $provider;
            }
            $provider = $provider['provider'];
            $sr = $this->srService->getRequestByName($provider, $data['name']);
            if ($sr instanceof Sr) {
                return [
                    'success' => false,
                    'message' => "Service Request {$data['name']} already exists for {$provider->name}."
                ];
            }
            if (!$this->srService->createServiceRequest($provider, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create provider."
                ];
            }
            if ($withChildren) {
                return [
                    'success' => true,
                    'message' => "Service Request {$data['name']} created for {$provider->name}.",
                    'data' => $this->importSrChildren(ImportAction::CREATE, $sr, $data, true),
                ];
            }
            return [
                'success' => true,
                'message' => "Service Request {$data['name']} created for {$provider->name}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    private function findProvider(array $data)
    {
            if (!empty($data['provider'])) {
                $provider = $data['provider'];
            } elseif (!empty($data['pivot']['provider_id'])) {
                $provider = $this->providerService->getProviderById($data['pivot']['provider_id']);
            } elseif (!empty($data['provider_id'])) {
                $provider = $this->providerService->getProviderById($data['provider_id']);
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
            return [
                'success' => true,
                'provider' => $provider
            ];
    }

    public function importSrChildren(ImportAction $action, Sr $sr, array $data, bool $withChildren): array
    {
            $response = [];
            $data['sr'] = $sr;
            $data['sr_id'] = $sr->id;
            if (
                !empty($data['sr_rate_limit']) &&
                is_array($data['sr_rate_limit'])
            ) {
                $data['sr_rate_limit']['sr'] = $sr;
                $data['sr_rate_limit']['sr_id'] = $sr->id;
                $this->srRateLimitImporterService->setUser($this->getUser());
                $response[] = $this->srRateLimitImporterService->import(
                    $action,
                    $data['sr_rate_limit'],
                    $withChildren
                );
            }
            if (
                !empty($data['sr_schedule']) &&
                is_array($data['sr_schedule'])
            ) {
                $data['sr_schedule']['sr'] = $sr;
                $data['sr_schedule']['sr_id'] = $sr->id;
                $this->srScheduleImporterService->setUser($this->getUser());
                $response[] = $this->srScheduleImporterService->import(
                    $action,
                    $data['sr_schedule'],
                    $withChildren
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
                        array_map(function ($parameter) use ($data) {
                            $parameter['sr_id'] = $data['sr_id'];
                            return $parameter;
                        }, $data['sr_response_keys']),
                        $withChildren
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
                        array_map(function ($parameter) use ($data) {
                            $parameter['sr_id'] = $data['sr_id'];
                            return $parameter;
                        }, $data['sr_parameter']),
                        $withChildren
                    )
                );
            }
            if (
                !empty($data['sr_config']) &&
                is_array($data['sr_config'])
            ) {
                $data['sr_config']['sr'] = $sr;
                $data['sr_config']['sr_id'] = $sr->id;
                $this->srConfigImporterService->setUser($this->getUser());
                $response[] = $this->srConfigImporterService->import(
                    $action,
                    $data['sr_config'],
                    $withChildren
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
                        $data['child_srs'],
                        $withChildren
                    )
                );
            }
            return [
                'success' => true,
                'data' => $response,
            ];
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array {

        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {
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

    public function filterData(array $data): array {
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
    public function filterImportData(array $data): array {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch(
                $this->filterData($data)
            )
        ];
    }
    public function parseEntity(array $entity): array {

        $entity['children'] = [];
        if (
            !empty($entity['sr_rate_limit']) &&
            is_array($entity['sr_rate_limit'])
        ) {
//            $entity['sr_rate_limit'] = [$this->srRateLimitImporterService->filterImportData($entity['sr_rate_limit'])];
            $entity['children'][] = $this->srRateLimitImporterService->filterImportData($entity['sr_rate_limit']);
        }
        if (
            !empty($entity['sr_schedule']) &&
            is_array($entity['sr_schedule'])
        ) {
//            $entity['sr_schedule'] = [$this->srScheduleImporterService->filterImportData($entity['sr_schedule'])];
            $entity['children'][] = $this->srScheduleImporterService->filterImportData($entity['sr_schedule']);
        }
        if (
            !empty($entity['sr_response_keys']) &&
            is_array($entity['sr_response_keys'])
        ) {
//            $entity['sr_response_keys'] = [$this->srResponseKeysImporterService->filterImportData($entity['sr_response_keys'])];
            $entity['children'][] = $this->srResponseKeysImporterService->filterImportData($entity['sr_response_keys']);
        }
        if (
            !empty($entity['sr_parameter']) &&
            is_array($entity['sr_parameter'])
        ) {
//            $entity['sr_parameter'] = [$this->srParameterImporterService->filterImportData($entity['sr_parameter'])];
            $entity['children'][] = $this->srParameterImporterService->filterImportData($entity['sr_parameter']);
        }
        if (
            !empty($entity['sr_config']) &&
            is_array($entity['sr_config'])
        ) {
//            $entity['sr_config'] = [$this->srConfigImporterService->filterImportData($entity['sr_config'])];
            $entity['children'][] = $this->srConfigImporterService->filterImportData($entity['sr_config']);
        }
        if (
            !empty($entity['child_srs']) &&
            is_array($entity['child_srs'])
        ) {
//            $entity['child_srs'] = [$this->filterImportData($entity['child_srs'])];
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
}
