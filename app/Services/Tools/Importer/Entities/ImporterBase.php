<?php

namespace App\Services\Tools\Importer\Entities;

use App\Services\Permission\AccessControlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Database\Eloquent\Model;

class ImporterBase
{
    use ErrorTrait, UserTrait;

    protected Model $model;
    protected array $config;

    public function __construct(
        protected AccessControlService $accessControlService,
        Model $model
    )
    {
        $this->model = $model;
    }

    protected function compareKeysWithModelFields(array $data): bool {
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

    protected function compareItemKeysWithModelFields(array $data): bool {
        $modelKeys = $this->model->getFillable();
        if (count(array_diff($modelKeys, array_keys($data))) > 0) {
            return false;
        }
        return true;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    protected function addConfigItem(string $key, mixed $value): void {
        $this->config[$key] = $value;
    }

    public function getAccessControlService(): AccessControlService
    {
        return $this->accessControlService;
    }

}
