<?php

namespace App\Repositories;

use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
    use ErrorTrait;

    protected string $modelClassName;
    protected Model $model;
    private array $where = [];
    private string $sort = 'asc';
    private string $orderBy = 'id';
    private int $limit = -1;
    private int $offset = 0;

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

    public function findAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->modelClassName::all();
    }

    public function findById(int $id): ?Model
    {
        return $this->modelClassName::find($id);
    }

    private function buildQuery() {
        $query = $this->modelClassName::query();
        foreach ($this->where as $index => $where) {
            if ($index === 0) {
                $query->where($where['field'], $where['compare'], $where['value']);
                continue;
            }
            switch ($where['op']) {
                case 'OR':
                    $query->orWhere($where['field'], $where['compare'], $where['value']);
                    break;
                default:
                    $query->where($where['field'], $where['compare'], $where['value']);
                    break;
            }
        }
        $query->orderBy($this->orderBy, $this->sort);
        if ($this->limit > 0) {
            $query->limit($this->limit);
        }
        if ($this->offset > 0) {
            $query->offset($this->offset);
        }
        return $query;
    }

    public function findOne(): ?Model
    {
        return $this->buildQuery()->first();
    }
    public function findMany(): ?Model
    {
        return $this->buildQuery()->all();
    }

    public function findByOrFail(string $field, string $value): Model
    {
        return $this->modelClassName::where($field, $value)->firstOrFail();
    }

    public function findByOrFailWith(string $field, string $value, array $with): Model
    {
        return $this->modelClassName::with($with)->where($field, $value)->firstOrFail();
    }

    public function findByWith(string $field, string $value, array $with): ?Model
    {
        return $this->modelClassName::with($with)->where($field, $value)->first();
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

    public function setModel(Model $model): self
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

    public function addWhere(string $field, string $value, ?string $compare = '=', ?string $op = 'AND'): self
    {
        $this->where[] = [
            'field' => $field,
            'value' => $value,
            'compare' => $compare,
            'op' => $op
        ];
    }
    public function getWhere(): array
    {
        return $this->where;
    }

    public function setWhere(array $where): self
    {
        $this->where = $where;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function setSort(string $sort): self
    {
        $this->sort = $sort;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
    }

}
