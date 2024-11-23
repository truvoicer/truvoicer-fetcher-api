<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
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

    abstract public function import(array $data, array $mappings = []): array;

    abstract public function importSelfNoChildren(array $map, array $data): array;
    abstract public function importSelfWithChildren(array $map, array $data): array;

    abstract public function validateImportData(array $data): void;

    abstract public function filterImportData(array $data): array;

    abstract public function getExportData(): array;

    abstract public function getExportTypeData($item): array|bool;

    abstract public function parseEntity(array $entity): array;

    abstract public function parseEntityBatch(array $data): array;


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

}
