<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\EntityService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

abstract class ImporterBase
{
    use ErrorTrait, UserTrait;

    protected SrService $srService;
    protected ProviderService $providerService;
    protected Model $model;
    protected array $config;
    protected array $mappings;
    protected EntityService $entityService;

    public function __construct(
        protected AccessControlService $accessControlService,
        Model                          $model
    )
    {
        $this->srService = App::make(SrService::class);
        $this->providerService = App::make(ProviderService::class);
        $this->entityService = App::make(EntityService::class);
        $this->model = $model;
        $this->setConfig();
        $this->setMappings();
    }

    abstract protected function setConfig(): void;

    abstract protected function setMappings(): void;

    abstract protected function loadDependencies(): void;

    abstract public function validateImportData(array $data): void;

    abstract public function filterImportData(array $data): array;

    abstract public function getExportData(): array;

    abstract public function getExportTypeData($item): array|bool;

    abstract public function parseEntity(array $entity): array;

    abstract public function parseEntityBatch(array $data): array;

    abstract protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array;

    abstract protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array;

    abstract protected function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null;

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        return [
            'success' => true,
        ];
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data, ?array $dest = null, ?bool $lock = false): array
    {
        return $this->importSelf($action, $map, $data, false, $dest, $lock);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data, ?array $dest = null, ?bool $lock = false): array
    {
        return $this->importSelf($action, $map, $data, true, $dest, $lock);
    }

    protected function overwriteOrCreate(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        $overwrite = $this->overwrite($data, $withChildren, $map, $dest, $extraData);
        if ($overwrite['success']) {
            return $overwrite;
        }
        return $this->create($data, $withChildren, $map, $dest, $extraData);
    }

    public function importMapFactory(array $map, array $data, ?array $dest = null, ?bool $lock = false): array
    {
        if (empty($map['action'])) {
            return [
                'success' => false,
                'error' => 'No action found.',
            ];
        }
        $action = ImportAction::tryFrom($map['action']);
        if (!$action) {
            return [
                'success' => false,
                'error' => 'Invalid action found.',
            ];
        }

        switch ($action) {
            case ImportAction::CREATE:
            case ImportAction::OVERWRITE:
            case ImportAction::OVERWRITE_OR_CREATE:
                return match ($map['mapping']['name']) {
                    ImportMappingType::SELF_NO_CHILDREN->value => $this->importSelfNoChildren($action, $map, $data, $dest, $lock),
                    ImportMappingType::SELF_WITH_CHILDREN->value => $this->importSelfWithChildren($action, $map, $data, $dest, $lock),
                    default => [
                        'success' => false,
                        'data' => $map['data'],
                    ],
                };
        }
    }

    protected function batchImport(ImportAction $action, array $data, bool $withChildren, array $map, ?array $extraData = []): array
    {
        $response = [];
        foreach ($data as $provider) {
            $response[] = $this->import($action, $provider, $withChildren, $map, null, $extraData);
        }
        return $response;
    }

    public function import(ImportAction $action, array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = [], ?bool $lock = false): array
    {
        $this->loadDependencies();

        $lockableActions = [
            ImportAction::OVERWRITE,
            ImportAction::OVERWRITE_OR_CREATE,
        ];
        if (!$lock) {
            return match ($action) {
                ImportAction::CREATE => $this->create($data, $withChildren, $map, $dest, $extraData),
                ImportAction::OVERWRITE => $this->overwrite($data, $withChildren, $map, $dest, $extraData),
                ImportAction::OVERWRITE_OR_CREATE => $this->overwriteOrCreate($data, $withChildren, $map, $dest, $extraData),
            };
        }
        if (in_array($action, $lockableActions)) {
            return $this->lock($action, $map, $data, $dest);
        }
        return [
            'success' => true,
            'message' => 'Action is not lockable.'
        ];
    }

    protected function compareKeysWithModelFields(array $data): bool
    {
        $modelKeys = $this->model->getFillable();
        //validate data has same keys as model
        foreach ($data as $category) {
            if (!$this->compareItemKeysWithModelFields($category)) {
                $this->addError(
                    'import_type_validation',
                    "Data keys do not match model fields."
                );
                return false;
            }
        }

        return true;
    }

    protected function compareItemKeysWithModelFields(array $data): bool
    {
        $modelKeys = $this->model->getFillable();
        if (count(array_diff($modelKeys, array_keys($data))) > 0) {
            return false;
        }
        return true;
    }

    protected function filterMapData(array $map): array
    {
        return array_filter($map, function ($key) {
            return $key !== 'mapping';
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getConfig(): array
    {
        return [
            ...$this->config,
            ImportConfig::IMPORT_MAPPINGS->value => array_map(function ($map) {
                $map['source'] = $this->getConfigItem(ImportConfig::NAME);
                return $map;
            }, $this->mappings)
        ];
    }

    public function getMappings(): array
    {
        return $this->mappings;
    }

    protected function buildConfig(
        bool    $show,
        string  $id,
        string  $name,
        string  $label,
        ?string $nameField = null,
        ?string $labelField = null,
        ?string $rootLabelField = null,
        ?array  $childrenKeys = [],
    ): void
    {
        $this->config[ImportConfig::SHOW->value] = $show;
        $this->config[ImportConfig::ID->value] = $id;
        $this->config[ImportConfig::NAME->value] = $name;
        $this->config[ImportConfig::LABEL->value] = $label;
        $this->config[ImportConfig::CHILDREN_KEYS->value] = $childrenKeys;
        $this->config[ImportConfig::NAME_FIELD->value] = $nameField;
        $this->config[ImportConfig::LABEL_FIELD->value] = $labelField;
        $this->config[ImportConfig::ROOT_LABEL_FIELD->value] = $rootLabelField;
    }

    protected function getConfigItem(ImportConfig $importConfig): mixed
    {
        if (!array_key_exists($importConfig->value, $this->config)) {
            return null;
        }
        return $this->config[$importConfig->value];
    }

    public function getAccessControlService(): AccessControlService
    {
        return $this->accessControlService;
    }

    protected function importSelf(ImportAction $action, array $map, array $data, bool $withChildren, ?array $dest = null, ?bool $lock = false): array
    {
        $this->loadDependencies();
        if (!empty($map['root']) && !empty($map['children']) && is_array($map['children']) && count($map['children'])) {
            return [
                'success' => true,
                'data' => array_map(function ($map) use ($data, $action, $dest, $lock) {
                    $map['action'] = $action->value;
                    return $this->importMapFactory($map, $data, $dest, $lock);
                }, $map['children'])
            ];
        }

        $findItemIndex = array_search($map['id'], array_column($data, 'id'));
        if ($findItemIndex === false) {
            return [
                'success' => false,
                'error' => "Map item not found in data, make sure to include data | Import type: {$action->value} | Map id: {$map['id']}",
                'map' => $map,
            ];
        }
        return $this->import($action, $data[$findItemIndex], $withChildren, $map, $dest, [], $lock);
    }

    protected function findProvider(ImportType $importType, array $data, array $map, ?array $dest = null): array
    {
        if (!empty($data['provider'])) {
            $provider = $data['provider'];
        } elseif (
            !empty($map['mapping']['dest']) &&
            $map['mapping']['dest'] === ImportType::PROVIDER->value &&
            is_array($dest)
        ) {
            $destIndex = count($dest) > 0 ? 0 : false;
            if ($destIndex === false) {
                return [
                    'success' => false,
                    'message' => "Provider not found | Import type: {$importType->value}"
                ];
            }
            if (!empty($dest[$destIndex]['id'])) {
                $provider = $this->providerService->getProviderById((int)$dest[$destIndex]['id']);
            } else {
                return [
                    'success' => false,
                    'message' => "Provider is required | Import type: {$importType->value}"
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => "Provider is required | Import type: {$importType->value}"
            ];
        }
        if (!$provider instanceof Provider) {
            return [
                'success' => false,
                'message' => "Provider not found | Import type: {$importType->value}"
            ];
        }
        return [
            'success' => true,
            'provider' => $provider
        ];
    }

    public function findSr(ImportType $importType, array $data, array $map, ?array $dest = null): array
    {
        if (!empty($data['sr'])) {
            $sr = $data['sr'];
        } elseif (
            !empty($map['mapping']['dest']) &&
            $map['mapping']['dest'] === ImportType::SR->value &&
            is_array($dest)
        ) {
            $destIndex = count($dest) > 0 ? 0 : false;
            if ($destIndex === false) {
                return [
                    'success' => false,
                    'message' => "Sr not found for sr schedule | Import type: {$importType->value}"
                ];
            }
            if (!empty($dest[$destIndex]['id'])) {
                $sr = $this->srService->getServiceRequestById((int)$dest[$destIndex]['id']);
            } else {
                return [
                    'success' => false,
                    'message' => "Sr is required for sr schedule | Import type: {$importType->value}"
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => "Sr is required for sr schedule | Import type: {$importType->value}"
            ];
        }
        if (!$sr instanceof Sr) {
            return [
                'success' => false,
                'message' => "Sr not found for sr schedule | Import type: {$importType->value}"
            ];
        }
        return [
            'success' => true,
            'sr' => $sr
        ];
    }

}
