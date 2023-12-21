<?php

namespace App\Repositories;

use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
    use ErrorTrait;

    const DEFAULT_WHERE = [];
    const DEFAULT_SORT = 'asc';
    const DEFAULT_ORDER_BY = 'id';
    const DEFAULT_LIMIT = -1;
    const DEFAULT_OFFSET = 0;
    protected string $modelClassName;
    protected Model $model;
    private array $where = self::DEFAULT_WHERE;
    private string $sort = self::DEFAULT_SORT;
    private string $orderBy = self::DEFAULT_ORDER_BY;
    private int $limit = self::DEFAULT_LIMIT;
    private int $offset = self::DEFAULT_OFFSET;

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
        $find = $this->modelClassName::all();
        $this->reset();
        return $find;
    }

    public function findById(int $id): ?Model
    {
        $find = $this->modelClassName::find($id);
        $this->reset();
        return $find;
    }

    public function findByName(string $name = null)
    {
        $this->addWhere('name', $name);
        return $this->findOne();
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

    protected function reset() {
        $this->where = self::DEFAULT_WHERE;
        $this->sort = self::DEFAULT_SORT;
        $this->orderBy = self::DEFAULT_ORDER_BY;
        $this->limit = self::DEFAULT_LIMIT;
        $this->offset = self::DEFAULT_OFFSET;
    }

    public function findOne(): ?Model
    {
        $find = $this->buildQuery()->first();
        $this->reset();
        return $find;
    }
    public function findMany(): ?Model
    {
        $find = $this->buildQuery()->all();
        $this->reset();
        return $find;
    }

    public function findByLabelOrName($query)
    {
        $this->addWhere("label", "LIKE", "%$query%");
        $this->addWhere("name", "LIKE", "%$query%", "OR");
        return $this->findMany();
    }

    public function findAllWithParams(string $sort = "name", ?string $order = "asc", ?int $count= null) {
        $this->setOrderBy($order);
        $this->setSort($sort);
        $this->setLimit($count);
        return $this->findMany();
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
        $this->model = $this->getModelInstance($data);
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
        return $this;
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
        return $this;
    }
    public function getWhere(): array
    {
        return $this->where;
    }

    public function setWhere(array $where): self
    {
        $this->where = $where;
        return $this;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function setSort(string $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}
