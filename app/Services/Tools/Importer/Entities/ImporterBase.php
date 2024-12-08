<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Provider;
use App\Services\Permission\AccessControlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Database\Eloquent\Model;

abstract class ImporterBase
{
    use ErrorTrait, UserTrait;

    protected Model $model;
    protected array $config;
    protected array $mappings;

    public function __construct(
        protected AccessControlService $accessControlService,
        Model                          $model
    )
    {
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

    abstract protected function overwrite(array $data, bool $withChildren): array;

    abstract protected function create(array $data, bool $withChildren): array;

    abstract protected function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null;

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array
    {
        return $this->importSelf($action, $map, $data, true);
    }

    protected function overwriteOrCreate(array $data, bool $withChildren): array
    {
        $overwrite = $this->overwrite($data, $withChildren);
        if ($overwrite['success']) {
            return $overwrite;
        }
        return $this->create($data, $withChildren);
    }

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        return match ($action) {
            ImportAction::CREATE => $this->create($data, $withChildren),
            ImportAction::OVERWRITE => $this->overwrite($data, $withChildren),
            ImportAction::OVERWRITE_OR_CREATE => $this->overwriteOrCreate($data, $withChildren),
            default => [
                'success' => false,
                'data' => $data,
                'error' => 'Invalid action.'
            ],
        };
    }

    public function importMapFactory(array $map, array $data): array
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

        return match ($map['mapping']['name']) {
            ImportMappingType::SELF_NO_CHILDREN->value => $this->importSelfNoChildren($action, $map, $data),
            ImportMappingType::SELF_WITH_CHILDREN->value => $this->importSelfWithChildren($action, $map, $data),
            default => [
                'success' => false,
                'data' => $map['data'],
            ],
        };
    }

    protected function batchImport(ImportAction $action, array $data, bool $withChildren): array
    {
        $response = [];
        foreach ($data as $provider) {
            $response[] = $this->import($action, $provider, $withChildren);
        }
        return $response;
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
        bool $show,
        string  $id,
        string  $name,
        string  $label,
        ?string  $nameField = null,
        ?string  $labelField = null,
        ?string  $rootLabelField = null,
        ?array   $childrenKeys = [],
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

    protected function importSelf(ImportAction $action, array $map, array $data, bool $withChildren): array {
        $this->loadDependencies();
        if (!empty($map['root']) && !empty($map['children']) && is_array($map['children']) && count($map['children'])) {
            return [
                'success' => true,
                'data' => array_map(function ($map) use ($data, $action) {
                    $map['action'] = $action->value;
                    return $this->importMapFactory($map, $data);
                }, $map['children'])
            ];
        }
        $findItemIndex = array_search($map['id'], array_column($data, 'id'));
        if ($findItemIndex === false) {
            return [
                'success' => false,
                'data' => $map,
                'error' => 'Category not found'
            ];
        }
        return $this->import($action, $data[$findItemIndex], $withChildren);
    }

}
