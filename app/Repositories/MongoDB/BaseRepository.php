<?php

namespace App\Repositories\MongoDB;

use App\Helpers\Db\DbHelpers;
use App\Traits\Database\PaginationTrait;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Connection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BaseRepository
{

    use ErrorTrait, PaginationTrait;

    const DEFAULT_WHERE = [];
    const AVAILABLE_ORDER_DIRECTIONS = ['asc', 'desc'];
    const DEFAULT_SORT_FIELD = 'id';
    const DEFAULT_ORDER_DIR = 'asc';
    const DEFAULT_LIMIT = -1;
    const DEFAULT_OFFSET = 0;
    protected DbHelpers $dbHelpers;
    private array $where = self::DEFAULT_WHERE;
    private array $whereGroups = [];
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

    public function getCollection(): string
    {
        return $this->collection;
    }


    public function findAll()
    {
        return $this->getResults(
            $this->connection->collection($this->collection)
        );
    }

    public function findById(int $id): ?object
    {
        return $this->connection->collection($this->collection)->find($id);
    }

    public function getResults($query): Collection|LengthAwarePaginator
    {
        if ($this->paginate) {
            return $query->paginate($this->perPage, ['*'], 'page', $this->page);
        }
        return $query->get();
    }

    private function getOrWhereCompareQuery(int $index, $where, $query) {

        switch ($where['compare']) {
            case 'IN':
                $query->orWhereIn($where['field'], $where['value']);
                break;
            case 'NOT IN':
                $query->orWhereNotIn($where['field'], $where['value']);
                break;
            case 'BETWEEN':
                $query->orWhereBetween($where['field'], $where['value']);
                break;
            case 'NOT BETWEEN':
                $query->orWhereNotBetween($where['field'], $where['value']);
                break;
            case 'LIKE':
                $query->orWhere($where['field'], 'like', $where['value']);
                break;
            case 'NOT LIKE':
                $query->orWhere($where['field'], 'not like', $where['value']);
                break;
            case 'NULL':
                $query->orWhereNull($where['field']);
                break;
            case 'NOT NULL':
                $query->orWhereNotNull($where['field']);
                break;
            case 'elemMatch':
                $query->orWhere($where['field'], 'elemMatch', $where['value']);
                break;
            default:
                $query->orWhere($where['field'], $where['compare'], $where['value']);
                break;
        }
        return $query;
    }
    private function getWhereCompareQuery(int $index, $where, $query) {
        switch ($where['compare']) {
            case 'IN':
                $query->whereIn($where['field'], $where['value']);
                break;
            case 'NOT IN':
                $query->whereNotIn($where['field'], $where['value']);
                break;
            case 'BETWEEN':
                $query->whereBetween($where['field'], $where['value']);
                break;
            case 'NOT BETWEEN':
                $query->whereNotBetween($where['field'], $where['value']);
                break;
            case 'LIKE':
                $query->where($where['field'], 'like', $where['value']);
                break;
            case 'NOT LIKE':
                $query->where($where['field'], 'not like', $where['value']);
                break;
            case 'NULL':
                $query->whereNull($where['field']);
                break;
            case 'NOT NULL':
                $query->whereNotNull($where['field']);
                break;
            case 'elemMatch':
                $query->where($where['field'], 'elemMatch', $where['value']);
                break;
            default:
                $query->where($where['field'], $where['compare'], $where['value']);
                break;
        }
        return $query;
    }
    private function getWhereQuery(int $index, $where, $query) {

        if ($index === 0) {
            $this->getWhereCompareQuery($index, $where, $query);
            return;
        }
        switch ($where['op']) {
            case 'OR':
                $this->getOrWhereCompareQuery($index, $where, $query);
                break;
            default:
                $this->getWhereCompareQuery($index, $where, $query);
                break;
        }
    }
    private function buildQuery() {
        $query = $this->connection->collection($this->collection);
        $query->where(function ($query) {
            foreach ($this->where as $index => $where) {
                $this->getWhereQuery($index, $where, $query);
            }
        });
        if (count($this->whereGroups)) {
            foreach ($this->whereGroups as $index => $whereGroup) {
                if ($index === 0) {
                    $query->where(function ($query) use ($whereGroup) {
                        foreach ($whereGroup['where'] as $index => $where) {
                            $this->getWhereQuery($index, $where, $query);
                        }
                    });
                    continue;
                }
                switch ($whereGroup['op']) {
                    case 'OR':
                        $query->orWhere(function ($query) use ($whereGroup) {
                            foreach ($whereGroup['where'] as $index => $where) {
                                $this->getWhereQuery($index, $where, $query);
                            }
                        });
                        break;
                    default:
                        $query->where(function ($query) use ($whereGroup) {
                            foreach ($whereGroup['where'] as $index => $where) {
                                $this->getWhereQuery($index, $where, $query);
                            }
                        });
                        break;
                }
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
    public function findMany(): Collection|LengthAwarePaginator
    {
        $find = $this->getResults(
            $this->getQuery()
        );
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
        $this->where[] = $this->buildWhereData($field, $value, $compare, $op);
        return $this;
    }

    public function addMatchArrayElement(string $field, array $value, ?string $op = 'AND'): array
    {
        return $this->buildWhereData(
            $field,
            $value,
            'elemMatch',
            $op
        );
    }
    public function buildWhereData(string $field, $value, ?string $compare = '=', ?string $op = 'AND'): array
    {
        return [
            'field' => $field,
            'value' => $value,
            'compare' => $compare,
            'op' => $op
        ];
    }
    public function addWhereGroup(array $whereData, ?string $op = 'AND'): self
    {
        $this->whereGroups[] = [
            'where' => $whereData,
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

    public function getWhereGroups(): array
    {
        return $this->whereGroups;
    }

}
