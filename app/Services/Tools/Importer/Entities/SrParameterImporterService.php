<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrParameter;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrParameterImporterService extends ImporterBase
{

    public function __construct(
        private SrService $srService,
        private SrParametersService $srParametersService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrParameter());
    }

    protected function loadDependencies(): void
    {
        $this->srService->setThrowException(false);
        $this->srParametersService->setThrowException(false);
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_PARAMETER->value,
            'Sr Parameters',
            'name',
            '{name}: {value}',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr parameter to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    protected function overwrite(array $data, bool $withChildren): array
    {
        try {
            $sr = $this->findSr($data);
            if (!$sr['success']) {
                return $sr;
            }
            $sr = $sr['sr'];
            $query = $sr->srParameter()->where('name', $data['name']);
            $count = $query->count();
            if (!$count) {
                return [
                    'success' => false,
                    'message' => "Sr parameter for parameter {$data['name']} not found for sr {$sr->name}."
                ];
            }
            if ($count === 1) {
                $srParameter = $query->first();
                if (!$this->srParametersService->updateRequestParameter($srParameter, $data)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create sr parameter {$data['name']} for sr {$sr->name}.."
                    ];
                } else {
                    return [
                        'success' => true,
                        'message' => "Sr parameter {$data['name']} imported successfully for sr {$sr->name}."
                    ];
                }
            }
            if (!$this->srParametersService->createRequestParameter($sr, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create sr parameter {$data['name']} for sr {$sr->name}.."
                ];
            }
            return [
                'success' => true,
                'message' => "Sr parameter {$sr->name} imported successfully for sr {$sr->name}.."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $data
            ];
        }
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
            $sr = $this->findSr($data);
            if (!$sr['success']) {
                return $sr;
            }
            $sr = $sr['sr'];
            $srParameter = $sr->srParameter()->where('name', $data['name'])->first();
            if ($srParameter) {
                return [
                    'success' => false,
                    'message' => "Sr parameter {$data['name']} already exists for sr {$sr->name}."
                ];
            }

            if (!$this->srParametersService->createRequestParameter($sr, $data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create sr parameter {$data['name']} for sr {$sr->name}.."
                ];
            }
            return [
                'success' => true,
                'message' => "Sr parameter {$data['name']} imported successfully for sr {$sr->name}.."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $data
            ];
        }
    }

    public function findSr(array $data): array
    {
        if (!empty($data['sr'])) {
            $sr = $data['sr'];
        } elseif (!empty($data['sr_id'])) {
            $sr = $this->srService->getServiceRequestById((int)$data['sr_id']);
        } else {
            return [
                'success' => false,
                'message' => "Sr is required for parameter {$data['name']}."
            ];
        }
        if (!$sr instanceof Sr) {
            return [
                'success' => false,
                'message' => "Sr not found for parameter {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'sr' => $sr
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
        foreach ($data as $parameter) {
            if (empty($parameter['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request name is required."
                );
            }
            if (empty($parameter['value'])) {
                $this->addError(
                    'import_type_validation',
                    "Service Request label is required."
                );
            }
        }
    }

    public function filterImportData(array $data): array {
        $filter =  array_filter($data, function ($sr) {
            return (
                !empty($sr['name']) &&
                !empty($sr['value'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
        ];

    }
    public function parseEntity(array $entity): array {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getSrParametersService(): SrParametersService
    {
        return $this->srParametersService;
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
        return null;
    }

}
