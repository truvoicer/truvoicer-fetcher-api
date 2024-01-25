<?php

namespace App\Repositories\MongoDB;

use App\Helpers\Db\DbHelpers;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BaseRepository
{

    use ErrorTrait;

    const DEFAULT_WHERE = [];
    const AVAILABLE_ORDER_DIRECTIONS = ['asc', 'desc'];
    const DEFAULT_SORT_FIELD = 'id';
    const DEFAULT_ORDER_DIR = 'asc';
    const DEFAULT_LIMIT = -1;
    const DEFAULT_OFFSET = 0;
    protected DbHelpers $dbHelpers;
    private array $where = self::DEFAULT_WHERE;
    private string $sortField = self::DEFAULT_SORT_FIELD;
    private string $orderDir = self::DEFAULT_ORDER_DIR;
    private int $limit = self::DEFAULT_LIMIT;
    private int $offset = self::DEFAULT_OFFSET;

    private string $collection;
    private Connection $connection;
    /**
     * @throws \Exception
     */
    public function __construct()
    {
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
        $this->connection = DB::connection('mongodb');
    }



    public function findAll()
    {
        return $this->connection->collection($this->collection)->get();
    }

    public function findById(int $id): ?object
    {
        return $this->connection->collection($this->collection)->find($id);
    }

    private function buildQuery() {
        $query = $this->connection->collection($this->collection);
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
            $query->take($this->limit);
        }
        if ($this->offset > 0) {
            $query->skip($this->offset);
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

    public function findOne()
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
            $conditionCount = count($condition);
            if ($conditionCount !== 3 && $conditionCount !== 2) {
                return false;
            }
            if ($conditionCount === 2) {
                list($column, $value) = $condition;
                $comparison = '=';
            } else {
                list($column, $value, $comparison) = $condition;
            }
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

    public function insert(array $data) {
        return $this->connection->collection($this->collection)->insert($data);
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
    public function addWhere(string $field, $value, ?string $compare = '=', ?string $op = 'AND'): self
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

}
