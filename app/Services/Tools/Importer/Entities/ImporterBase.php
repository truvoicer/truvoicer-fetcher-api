<?php

namespace App\Services\Tools\Importer\Entities;

use Illuminate\Database\Eloquent\Model;

class ImporterBase
{
    protected Model $model;
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function compareKeysWithModelFields(array $data): bool {
        $modelKeys = $this->model->getFillable();
        //validate data has same keys as model
        foreach ($data as $category) {
            if (count(array_diff($modelKeys, array_keys($category))) > 0) {
                return false;
            }
        }

        return true;
    }
}
