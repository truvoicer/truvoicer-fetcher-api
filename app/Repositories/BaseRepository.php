<?php

namespace App\Repositories;

use App\Helpers\Db\DbHelpers;
use App\Models\User;
use App\Traits\Database\PaginationTrait;
use App\Traits\Database\PermissionsTrait;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseRepository
{
    use ErrorTrait, PermissionsTrait, PaginationTrait;

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

    private array $whereDoesntHave = [];
    private array $whereHas = [];
    private array $with = [];

    protected Relation|Builder|EloquentBuilder|null $query = null;

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

    public function buildCloneEntityStr($query, string $field, string $str, string $separator = '-cloned-'): string{

        $all = $query->pluck($field)->toArray();
        $mapNumber = array_map(function($item) use ($separator) {
            preg_match('/^.+?(\d{1,10})$/', $item, $matches);
            if (empty($matches)) {
                return false;
            }
            return (int)$matches[1];
        }, $all);

        $mapNumber = array_filter($mapNumber, fn ($item) => $item !== false);
        $max = 1;
        if (!empty($mapNumber)) {
            $max = max($mapNumber);
        }
        $counter = $max;
        while ($this->model->where($field, $str)->exists()) {
            if (str_ends_with($str, $separator . $counter - 1)) {
                $str = substr($str, 0, -strlen($separator . $counter - 1));
            }
            $str = $str . $separator . $counter;


            $counter++;
        }
        return $str;
    }

    public function getWhereDoesntHave(): array
    {
        return $this->whereDoesntHave;
    }

    public function setWhereDoesntHave(array $whereDoesntHave): self
    {
        $this->whereDoesntHave = $whereDoesntHave;
        return $this;
    }

    public function getWhereHas(): array
    {
        return $this->whereHas;
    }

    public function setWhereHas(array $whereHas): self
    {
        $this->whereHas = $whereHas;
        return $this;
    }

    public function getWith(): array
    {
        return $this->with;
    }

    public function setWith(array $with): self
    {
        $this->with = $with;
        return $this;
    }

    protected function addWhereDoesntHaveToQuery(EloquentBuilder|Relation $query): EloquentBuilder|Relation
    {
        $whereDoesntHave = $this->getWhereDoesntHave();
        foreach ($whereDoesntHave as $key => $value) {
            if ($value instanceof \Closure && is_string($key)) {
                $query->whereDoesntHave($key, $value);
            } else if (is_string($value)) {
                $query->whereDoesntHave($value);
            }
        }
        return $query;
    }
    protected function addWithToQuery(EloquentBuilder|Relation $query): EloquentBuilder|Relation
    {
        $query->with($this->getWith());
        return $query;
    }
    public function findModelsByUser(Model $model, User $user, ?bool $checkPermissions = true)
    {
        return $this->getResults(
            $this->getModelByUserQuery($model, $user, $checkPermissions)
        );
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
        $this->addWhere('id', $id);
        return $this->findOne();
    }

    public function findByName(string $name = null)
    {
        $this->addWhere('name', $name);
        return $this->findOne();
    }

    private function buildWhereQuery(Builder|EloquentBuilder $query, array $whereData, ?string $operation = null): Builder|EloquentBuilder
    {
        switch ($operation) {
            case 'OR':
                switch (strtoupper($whereData['compare'])) {
                    case 'IN':
                        return $query->orWhereIn($whereData['field'], $whereData['value']);
                    case 'NOT IN':
                        return $query->orWhereNotIn($whereData['field'], $whereData['value']);
                    default:
                        return $query->orWhere($whereData['field'], $whereData['compare'], $whereData['value']);
                }
            default:
                switch (strtoupper($whereData['compare'])) {
                    case 'IN':
                        return $query->whereIn($whereData['field'], $whereData['value']);
                    case 'NOT IN':
                        return $query->whereNotIn($whereData['field'], $whereData['value']);
                    default:
                        return $query->where($whereData['field'], $whereData['compare'], $whereData['value']);
                }
        }
    }
    public function newQuery(): Builder|EloquentBuilder
    {
        return $this->modelClassName::query();
    }
    protected function buildQuery() {
        $query = $this->modelClassName::query();
        foreach ($this->where as $index => $where) {
            if ($index === 0) {
                $query = $this->buildWhereQuery($query, $where);
                continue;
            }
            $query = $this->buildWhereQuery($query, $where, $where['op']);
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
        $this->reset();
        return $query;
    }

    protected function reset() {
        $this->where = self::DEFAULT_WHERE;
        $this->sortField = self::DEFAULT_SORT_FIELD;
        $this->orderDir = self::DEFAULT_ORDER_DIR;
        $this->limit = self::DEFAULT_LIMIT;
        $this->offset = self::DEFAULT_OFFSET;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function findOne(): ?Model
    {
        $this->setQuery($this->buildQuery());
        $find = $this->getOneResult($this->getQuery());
        $this->reset();
        return $find;
    }
    public function findMany()
    {
        $this->setQuery($this->buildQuery());
        $find = $this->getResults($this->getQuery());
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

    protected function getResultsQuery(Relation|EloquentBuilder $query)
    {
        if ($query instanceof Relation || $query instanceof EloquentBuilder) {
            $query = $this->addWhereDoesntHaveToQuery($query);
            $query = $this->addWithToQuery($query);
        }

//        $query->offset($this->offset);
        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->orderDir);
        }
        if (!$this->paginate && $this->limit > -1) {
            $query->limit($this->limit);
        }
        return $query;
    }
    protected function getResults(Relation|EloquentBuilder $query): Collection|LengthAwarePaginator
    {
        if ($this->paginate) {
            return $query->paginate(
                ($this->perPage > -1) ? $this->perPage : 10000
            );
        }
        return $this->getResultsQuery($query)->get();
    }
    protected function getOneResult(Relation|EloquentBuilder $query): ?Model
    {
        return $this->getResultsQuery($query)->first();
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
            $this->addError('repository_insert_error', 'Error creating listing for user', $data);
            return false;
        }
        return true;
    }

    public function update(array $data) {
        $this->model->fill($data);
        $saveListing = $this->model->save();
        if (!$saveListing) {
            $this->addError('repository_update_error', 'Error saving listing', $data);
            return false;
        }
        return true;
    }

    public function deleteBatch(array $ids) {
        $ids = array_map('intval', $ids);
        foreach ($ids as $index => $id) {
            if ($index === 0) {
                $this->addWhere('id', $id);
                continue;
            }
            $this->addWhere('id', $id, '=', 'OR');
        }
        $this->setQuery($this->buildQuery());
        return $this->getQuery()->delete();
    }
    public function delete() {
        if (!$this->model->delete()) {
            $this->addError('repository_delete_error', 'Error deleting listing');
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

    public function addWhere(string $field, string|array $value, ?string $compare = '=', ?string $op = 'AND'): self
    {
        if (is_array($value) &&  !in_array(strtoupper($compare), ['IN', 'NOT IN'])) {
            return $this;
        }
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
