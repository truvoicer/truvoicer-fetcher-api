<?php

namespace App\Services\Tools\Importer\Entities;

use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Model;

class ImporterBase
{
    use ErrorTrait;

    protected Model $model;
    public function __construct(Model $model)
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
}
