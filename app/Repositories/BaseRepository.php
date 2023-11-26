<?php

namespace App\Repositories;

use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
    use ErrorTrait;

    protected string $modelClassName;
    protected Model $model;

    /**
     * @throws \Exception
     */
    public function __construct(string $modelClassName)
    {
        if ($this->validateModel($modelClassName)) {
            $this->modelClassName = $modelClassName;
        }
    }

    public function getModelInstance(?array $data = null): Model {
        if (is_array($data)) {
            return new $this->modelClassName($data);
        }
        return new $this->modelClassName();
    }

    /**
     * @throws \Exception
     */
    private function validateModel(string $modelClassName): bool {
        if (!class_exists($modelClassName)) {
            throw new \Exception("Model class not found | {$modelClassName}");
        }
        return true;
    }

    public function save(?array $data = []): bool
    {
        if (!$this->doesModelExist()) {
            return $this->insert($data);
        } else {
            return $this->update($data);
        }
    }
    public function insert(array $data) {
        if (!$this->isModelSet()) {
            $this->model = $this->getModelInstance($data);
        }
        $createListing = $this->model->save();
        if (!$createListing) {
            $this->addError('Error creating listing for user', $data);
            return false;
        }
        return true;
    }

    public function update(array $data) {
        $this->model->fill($data);
        $saveListing = $this->model->save();
        if (!$saveListing) {
            $this->addError('Error saving listing', $data);
            return false;
        }
        return true;
    }

    public function delete() {
        if (!$this->model->delete()) {
            $this->addError('Error deleting listing');
            return false;
        }
        return true;
    }

    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    protected function isModelSet(): bool
    {
        return isset($this->model);
    }
    public function doesModelExist(): bool
    {
        return (
            isset($this->model) &&
            $this->model->exists
        );
    }
}
