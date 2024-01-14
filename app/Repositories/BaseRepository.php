<?php

namespace App\Repositories;

use App\Helpers\Db\DbHelpers;
use App\Traits\Database\PermissionsTrait;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class BaseRepository
{
    use ErrorTrait, PermissionsTrait;

    const DEFAULT_WHERE = [];
    const AVAILABLE_ORDER_DIRECTIONS = ['asc', 'desc'];
    const DEFAULT_SORT_FIELD = 'id';
    const DEFAULT_ORDER_DIR = 'asc';
    const DEFAULT_LIMIT = -1;
    const DEFAULT_OFFSET = 0;
    protected DbHelpers $dbHelpers;
    protected string $modelClassName;
    protected object $model;
    private array $where = self::DEFAULT_WHERE;
    private string $sortField = self::DEFAULT_SORT_FIELD;
    private string $orderDir = self::DEFAULT_ORDER_DIR;
    private int $limit = self::DEFAULT_LIMIT;
    private int $offset = self::DEFAULT_OFFSET;

    /**
     * @throws \Exception
     */
    public function __construct(string $modelClassName)
    {
        if ($this->validateModel($modelClassName)) {
            $this->modelClassName = $modelClassName;
            $this->model = $this->getModelInstance();
        }
        $this->dbHelpers = new DbHelpers();
    }

    public function getModelInstance(?array $data = null): object {
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

    public function findAll(): Collection
    {
        $find = $this->modelClassName::all();
        $this->reset();
        return $find;
    }

    public function findByModel(Model $model): ?object
    {
        return $this->findById($model->id);
    }
    public function findById(int $id): ?object
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
        if (!in_array($this->orderDir, self::AVAILABLE_ORDER_DIRECTIONS)) {
            $this->orderDir = self::DEFAULT_ORDER_DIR;
        }
        $query->orderBy($this->sortField, $this->orderDir);
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
        $this->sortField = self::DEFAULT_SORT_FIELD;
        $this->orderDir = self::DEFAULT_ORDER_DIR;
        $this->limit = self::DEFAULT_LIMIT;
        $this->offset = self::DEFAULT_OFFSET;
    }

    public function getQuery()
    {
        $query = $this->buildQuery();
        $this->reset();
        return $query;
    }

    public function findOne(): ?Model
    {
        $find = $this->getQuery()->first();
        $this->reset();
        return $find;
    }
    public function findMany(): Collection
    {
        $find = $this->getQuery()->get();
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
        $this->setOrderDir($order);
        $this->setSortField($sort);
        if ($count !== null) {
            $this->setLimit($count);
        }
        return $this->findMany();
    }


    public function applyConditionsToQuery(array $conditions, $query) {
        foreach ($conditions as $condition) {
            if (count($condition) !== 3) {
                continue;
            }
            list($column, $value, $comparison) = $condition;
            $query->where($column, $comparison, $value);
        }
        return $query;
    }

    public function applyConditions(array $conditions) {
        foreach ($conditions as $condition) {
            if (count($condition) !== 3) {
                return false;
            }
            list($column, $value, $comparison) = $condition;
            $this->addWhere(
                $column,
                $value,
                $comparison,
            );
        }
        return true;
    }
    public function findOneBy(array $conditions) {
        if (!$this->applyConditions($conditions)) {
            return false;
        }
        return $this->findOne();
    }
    public function findManyBy(array $conditions) {
        if (!$this->applyConditions($conditions)) {
            return false;
        }
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

    public function deleteBatch(array $ids) {
        foreach ($ids as $index => $id) {
            if ($index === 0) {
                $this->addWhere('id', $id);
                continue;
            }
            $this->addWhere('id', $id, '=', 'OR');
        }
        return $this->getQuery()->delete();
    }
    public function delete() {
        if (!$this->model->delete()) {
            $this->addError('Error deleting listing');
            return false;
        }
        return true;
    }

    public function setModel(object $model): self
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

    public function getSortField(): string
    {
        return $this->sortField;
    }

    public function setSortField(string $sortField): self
    {
        $this->sortField = $sortField;
        return $this;
    }

    public function getOrderDir(): string
    {
        return $this->orderDir;
    }

    public function setOrderDir(string $orderDir): self
    {
        $this->orderDir = $orderDir;
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

    public function getModel(): object
    {
        return $this->model;
    }
}
