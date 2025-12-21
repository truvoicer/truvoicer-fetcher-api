<?php

namespace App\Repositories\MongoDB;

use App\Helpers\Db\DbHelpers;
use App\Traits\Database\PaginationTrait;
use App\Traits\Error\ErrorTrait;
use Illuminate\Database\Connection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MongoDB\Laravel\Query\Builder;
use MongoDB\BSON\ObjectId;

class MongoDBRaw
{

    use ErrorTrait, PaginationTrait;

    const DEFAULT_ORDER_DIR = 'asc';
    const DEFAULT_LIMIT = -1;
    const DEFAULT_OFFSET = 0;


    protected MongoAggregationBuilder $mongoAggregationBuilder;
    protected DbHelpers $dbHelpers;
    private string $collection;
    private Connection $connection;
    private Builder $table;

    /**
     * The MongoDB sort order array.
     *
     * @var array
     */
    protected array $sort = [];

    private int $limit = self::DEFAULT_LIMIT;
    private int $offset = self::DEFAULT_OFFSET;
    private array $query = [];
    private array $options = [];

    private bool $aggregation = false;

    /**
     * Configuration for injecting items at specific positions.
     * @var array
     */
    protected array $positionConfig = [];

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->mongoAggregationBuilder = new MongoAggregationBuilder();
    }
    /**
     * Sets the configuration for pinned/injected documents.
     * Format:
     * [
     * "start" => [ ["document_id" => "id..."] ],
     * "end"   => [ ["document_id" => "id..."] ],
     * "custom"=> [ ["document_id" => "id...", "insert_index" => 5] ]
     * ]
     */
    public function setPositionConfig(array $config): self
    {
        $this->positionConfig = $config;
        return $this;
    }

    /**
     * Helper to extract all pinned IDs to exclude them from the main query.
     */
    protected function getPinnedIds(): array
    {
        $ids = [];
        foreach (['start', 'end', 'custom'] as $key) {
            if (!empty($this->positionConfig[$key])) {
                foreach ($this->positionConfig[$key] as $item) {
                    if (isset($item['document_id'])) {
                        $ids[] = new ObjectId($item['document_id']);
                    }
                }
            }
        }
        return array_unique($ids); // Return as ObjectIds
    }

    /**
     * Helper to fetch the actual document objects for the pinned IDs.
     */
    protected function fetchPinnedDocuments(): Collection
    {
        $ids = $this->getPinnedIds();

        if (empty($ids)) {
            return new Collection();
        }

        // We use a fresh connection to fetch these specific IDs ignoring current query filters
        $cursor = $this->connection->table($this->collection)->raw(function ($collection) use ($ids) {
            return $collection->find(['_id' => ['$in' => $ids]]);
        });

        return new Collection($cursor->toArray());
    }

    /**
     * Merges the pinned documents into the main result set based on the current page.
     */
    protected function applyPositioning(Collection $mainResults): Collection
    {
        if (empty($this->positionConfig)) {
            return $mainResults;
        }

        // 1. Fetch Pinned Docs
        $pinnedDocs = $this->fetchPinnedDocuments()->keyBy(function ($item) {
            return (string)$item->_id;
        });

        $resultsArray = $mainResults->values()->all();

        // Calculate the global offset (how many items exist before this page)
        // If pagination is disabled (findAll), page is 1 and offset is 0.
        $currentPage = $this->paginate ? $this->page : 1;
        $perPage = $this->paginate ? $this->perPage : 999999;
        $pageStartOffset = ($currentPage - 1) * $perPage;
        $pageEndOffset = $pageStartOffset + $perPage;

        // -------------------------------------------------------
        // A. Handle "Start" (Only inject on Page 1)
        // -------------------------------------------------------
        if (!empty($this->positionConfig['start']) && $currentPage === 1) {
            $startItems = array_reverse($this->positionConfig['start']);
            foreach ($startItems as $config) {
                $id = $config['document_id'] ?? null;
                if ($id && $pinnedDocs->has($id)) {
                    array_unshift($resultsArray, $pinnedDocs->get($id));
                }
            }
        }

        // -------------------------------------------------------
        // B. Handle "Custom" (Check if index falls in current page)
        // -------------------------------------------------------
        if (!empty($this->positionConfig['custom'])) {
            // Sort by index DESC so splicing doesn't shift lower indices
            $customItems = collect($this->positionConfig['custom'])
                ->sortByDesc('insert_index')
                ->values()
                ->all();

            foreach ($customItems as $config) {
                $id = $config['document_id'] ?? null;
                $globalTargetIndex = $config['insert_index'] ?? 0;

                // Check if the Global Index falls within this specific Page
                if ($id && $pinnedDocs->has($id)) {
                    if ($globalTargetIndex >= $pageStartOffset && $globalTargetIndex < $pageEndOffset) {

                        // Calculate relative index for this specific page array
                        $relativeIndex = $globalTargetIndex - $pageStartOffset;

                        // Safety check: ensure we don't splice out of bounds of the current result chunk
                        if ($relativeIndex <= count($resultsArray)) {
                            array_splice($resultsArray, $relativeIndex, 0, [$pinnedDocs->get($id)]);
                        }
                    }
                }
            }
        }

        // -------------------------------------------------------
        // C. Handle "End" (Only inject on the very last page??)
        // -------------------------------------------------------
        // Context: "End" is tricky in pagination. Usually, users want "End" to mean
        // "Bottom of this specific fetch", not "Bottom of the database".
        // We will keep this as "Append to current result set" regardless of page.
        if (!empty($this->positionConfig['end'])) {
            foreach ($this->positionConfig['end'] as $config) {
                $id = $config['document_id'] ?? null;
                if ($id && $pinnedDocs->has($id)) {
                    $resultsArray[] = $pinnedDocs->get($id);
                }
            }
        }

        return new Collection($resultsArray);
    }

    /**
     * Internal method to apply the exclusion filter before running the main query.
     */
    protected function applyPinnedExclusion(): void
    {
        $pinnedIds = $this->getPinnedIds();
        if (!empty($pinnedIds)) {
            // Add a "Where ID Not In" condition
            // We manually construct the BSON here to match your addWhere style
            $this->addWhere('_id', 'nin', $pinnedIds);
        }
    }

    // -------------------------------------------------------------------------
    // MODIFIED FIND METHODS
    // -------------------------------------------------------------------------

    public function findAll(): Collection
    {
        // 1. Exclude pinned IDs so we don't fetch duplicates
        $this->applyPinnedExclusion();

        $query = $this->query;
        $options = $this->buildOptions();
        $this->reset();

        $aggregation = $this->aggregation;

        $cursor = $this->table->raw(function ($collection) use ($query, $options, $aggregation) {
            if ($aggregation) {
                return $collection->aggregate(
                    $this->addPaginationToPipeline($query),
                );
            }
            return $collection->find($query, $options);
        });

        $mainResults = new Collection($cursor->toArray());

        // 2. Inject the pinned items into the result set
        return $this->applyPositioning($mainResults);
    }
    public function findMany(): LengthAwarePaginator|Collection
    {
        if ($this->paginate) {
            $this->limit = $this->perPage;
            $this->offset = ($this->page - 1) * $this->perPage;
        }

        // 1. Exclude pinned IDs from main query
        $this->applyPinnedExclusion();

        // 2. Calculate how many items we are manually injecting
        $pinnedCount = count($this->getPinnedIds());

        $query = $this->query;
        $aggregation = $this->aggregation;
        $pipeline = $this->mongoAggregationBuilder->getPipeline();

        $countPipeline = $pipeline;

        $countPipeline[] = ['$count' => 'total'];
        $pipeline1[] = ['$count' => 'total'];

        $totalCursor = $this->table->raw(function ($collection) use ($query, $aggregation, $countPipeline) {
            if ($aggregation) {
                return $collection->aggregate($countPipeline);
            }
            return $collection->count($query);
        });

        $dbTotal = 0;
        if (is_int($totalCursor)) {
            $dbTotal = $totalCursor;
        } else {
            $totalArray = $totalCursor->toArray();
            $dbTotal = !empty($totalArray) ? $totalArray[0]->total : 0;
        }

        // CORRECT THE TOTAL: DB results + The items we forced out
        $finalTotalCount = $dbTotal + $pinnedCount;

        // Fetch actual data
        $options = $this->buildOptions();
        $this->reset();

        $cursor = $this->table->raw(function ($collection) use ($query, $options, $aggregation, $pipeline) {
            if ($aggregation) {
                return $collection->aggregate(
                    $this->addPaginationToPipeline($pipeline)
                );
            }
            return $collection->find($query, $options);
        });

        $mainResults = new Collection($cursor->toArray());

        // 3. Inject Pinned Items (Page Aware)
        $finalResults = $this->applyPositioning($mainResults);
        return $this->responseHandler($finalResults->toArray(), $finalTotalCount);
    }

    public function setAggregation(bool $aggregation): void
    {
        $this->aggregation = $aggregation;
    }


    public function getMongoAggregationBuilder(): MongoAggregationBuilder
    {
        return $this->mongoAggregationBuilder;
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
        $this->connection = DB::connection('mongodb');
        $this->table = $this->connection->table($collection);
    }
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setQuery(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getTable(): Builder
    {
        return $this->table;
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function setTable(Builder $table): self
    {
        $this->table = $table;
        return $this;
    }

    protected function reset()
    {
        $this->query = [];
        $this->options = [];
    }

    /**
     * Adds a "where" condition to the query.
     *
     * @param string $field The document field.
     * @param string $operator The comparison operator (e.g., '=', '>', 'in', 'like').
     * @param mixed $value The value to compare against.
     * @param string $condition The logical condition to join this where clause with ('and' or 'or').
     * @return $this
     */
    public function addWhere(string $field, string $operator, $value, string $condition = 'and'): self
    {
        $mongoOperator = $this->mapOperator($operator);

        // Create the BSON for the new condition, e.g., { "field": { "$operator": "value" } }
        $newConditionFragment = [];
        if ($mongoOperator === '$regex') {
            $newConditionFragment = [$field => ['$regex' => $value, '$options' => 'i']];
        } else {
            $newConditionFragment = [$field => [$mongoOperator => $value]];
        }

        $this->appendCondition($newConditionFragment, $condition);

        return $this;
    }

    /**
     * Adds a grouped "where" condition to the query.
     * Example: ->addWhereGroup('and', function($query) { $query->addWhere('a', '=', 1)->addWhere('b', '=', 2, 'or'); })
     *
     * @param string $condition The logical condition to join this group with ('and' or 'or').
     * @param \Closure $callback A closure that receives a new repository instance to build the grouped query.
     * @return $this
     */
    public function addWhereGroup(string $condition, \Closure $callback): self
    {
        // Create a new, clean instance of the current repository to build the sub-query.
        $repository = new static();

        // Execute the callback, which will call `addWhere` on the new repository instance.
        $callback($repository);

        // Get the generated query from the new repository.
        $groupQuery = $repository->getQuery();

        if (!empty($groupQuery)) {
            $this->appendCondition($groupQuery, $condition);
        }

        return $this;
    }

    public function addPriorityWhereGroup(
        array $priorityFields,
        array $mandatoryFields,
        string $condition = 'or'
    ): self {
        $this->aggregation = true;
        // Dynamically build the $match and $switch conditions from the columns array
        $matchOrConditions = [];
        $matchAndConditions = [];
        $matchConditions = [];
        $switchBranches = [];

        foreach ($priorityFields as $index => $field) {
            if (!array_key_exists('column', $field) || !array_key_exists('value', $field)) {
                continue;
            }
            if (is_string($field['value'])) {
                // Condition for matching the text in the column
                $matchOrConditions[] = [$field['column'] => ['$regex' => $field['value'], '$options' => 'i']];

                // Branch for the switch statement to assign priority
                $switchBranches[] = [
                    'case' => [
                        '$regexMatch' => [
                            'input' =>  [
                                '$toString' => '$' . $field['column']
                            ],
                            'regex' => $field['value'],
                            'options' => 'i'
                        ]
                    ],
                    'then' => $index + 1, // Priority will be 1, 2, 3, ...
                ];
            } elseif (is_integer($field['value'])) {
                $matchOrConditions[] = [$field['column'] => $field['value']];
                $switchBranches[] = [
                    'case' => ['$eq' => ['$' . $field['column'], $field['value']]],
                    'then' => $index + 1, // Priority will be 1, 2, 3, ...
                ];
            }
        }

        foreach ($mandatoryFields as $field) {
            if (!array_key_exists('column', $field) || !array_key_exists('value', $field)) {
                continue;
            }
            if (is_string($field['value'])) {
                $matchAndConditions[] = [$field['column'] => ['$regex' => $field['value'], '$options' => 'i']];
            } elseif (is_integer($field['value'])) {
                $matchAndConditions[] = [$field['column'] => $field['value']];
            }
        }

        if (count($matchAndConditions)) {
            $matchConditions = [...$matchConditions, ...$matchAndConditions];
        }
        if (count($matchOrConditions)) {
            $matchConditions[] = ['$or' => $matchOrConditions];
        }
        // dd($matchConditions);
        $pipeline = [
            // Stage 1: Match documents where the query exists in ANY of the specified columns
            [
                // '$match' => $matchConditions
                '$match' => [
                    '$and' => $matchConditions
                ]
            ]
        ];
        if (count($matchOrConditions)) {
            // Stage 2: Add a temporary field using $switch for multi-level priority
            $pipeline[] = [
                '$addFields' => [
                    'priority' => [
                        '$switch' => [
                            'branches' => $switchBranches,
                            'default' => 99, // Fallback priority, should not be reached due to the $match stage
                        ]
                    ]
                ]
            ];
            // Stage 3: Sort by the new priority field (this stage remains the same)
            $pipeline[] = [
                '$sort' => [
                    'priority' => 1 // Ascending order
                ]
            ];
        }
        // dd($pipeline);
        $this->appendCondition($pipeline, $condition);

        return $this;
    }


    /**
     * Appends a new condition or group to the main query array using a logical operator.
     *
     * @param array $newCondition The new BSON condition fragment or group to add.
     * @param string $logicalCondition The logical condition ('and' or 'or') to use.
     */
    private function appendCondition(array $newCondition, string $logicalCondition): void
    {
        $logicalCondition = strtolower($logicalCondition);

        // If there is no existing query, the new condition becomes the query.
        if (empty($this->query)) {
            $this->query = $newCondition;
            return;
        }

        $logicalOperator = '$' . $logicalCondition; // $and or $or

        // If the existing query is already a logical grouping of the SAME type,
        // we can simply add our new condition to it.
        if (isset($this->query[$logicalOperator]) && is_array($this->query[$logicalOperator])) {
            $this->query[$logicalOperator][] = $newCondition;
        } else {
            // Otherwise, we need to create a new logical grouping, wrapping the old
            // query and the new condition.
            $this->query = [
                $logicalOperator => [
                    $this->query, // The entire existing query
                    $newCondition
                ]
            ];
        }
    }

    /**
     * Maps common comparison operators to their MongoDB counterparts.
     *
     * @param string $operator The input operator.
     * @return string The corresponding MongoDB operator.
     */
    protected function mapOperator(string $operator): string
    {
        $operatorMap = [
            '=' => '$eq',
            '!=' => '$ne',
            '>' => '$gt',
            '>=' => '$gte',
            '<' => '$lt',
            '<=' => '$lte',
            'in' => '$in',
            'nin' => '$nin',
            'like' => '$regex',
        ];

        return $operatorMap[strtolower($operator)] ?? '$eq';
    }

    /**
     * Constructs the options array for MongoDB find operations.
     *
     * @param int|null $limit The maximum number of documents to return.
     * @param int|null $skip The number of documents to skip.
     * @return array The options array.
     */
    protected function buildOptions(): array
    {
        $options = [];
        if (isset($this->sort) && is_array($this->sort) && count($this->sort)) {
            $options['sort'] = $this->sort;
        }
        if (isset($this->limit) && is_int($this->limit)) {
            $options['limit'] = $this->limit;
        }
        if (isset($this->offset) && is_int($this->offset)) {
            $options['skip'] = $this->offset;
        }
        return $options;
    }

    /**
     * Adds a limit to the query.
     *
     * @param int $limit The maximum number of documents to return.
     * @return $this
     */
    public function addLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    /**
     * Adds an offset (skip) to the query.
     *
     * @param int $offset The number of documents to skip.
     * @return $this
     */
    public function addOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Adds a sort order to the query.
     *
     * @param string $field The field to sort by.
     * @param string $direction The sort direction ('asc' or 'desc').
     * @return $this
     */
    public function addSort(string $field, string $direction = 'asc'): self
    {
        $this->sort[$field] = strtolower($direction) === 'asc' ? 1 : -1;
        return $this;
    }

    /**
     * Finds a single document that matches the built query.
     *
     * @return object|null A single document object or null if not found.
     */
    public function findOne(): ?object
    {

        $query = $this->query;
        $this->addLimit(1);

        $options = $this->buildOptions();
        $this->reset(); // Reset state for the next query.
        $aggregation = $this->aggregation;
        dd($query, $options, $aggregation);
        $cursor = $this->table->raw(function ($collection) use ($query, $options, $aggregation) {
            if ($aggregation) {
                return $collection->aggregate(
                    $this->addPaginationToPipeline($query)
                );
            }
            return $collection->find($query, $options);
        });
        return new Collection($cursor->toArray())->first();
    }


    private function addPaginationToPipeline(?array $query = [])
    {
        $options = $this->buildOptions();
        if (isset($options['sort'])) {
            $query[] = [
                '$sort' => $options['sort']
            ];
        }
        if (isset($options['skip'])) {
            $query[] = [
                '$skip' => $options['skip'] ?? 0
            ];
        }
        if (isset($options['limit'])) {
            $query[] = [
                '$limit' => $options['limit']
            ];
        }
        return $query;
    }

    private function responseHandler(array $items, int $totalCount): LengthAwarePaginator|Collection
    {
        if ($this->paginate) {
            // If pagination is not enabled, return all items in a single page.
            return new LengthAwarePaginator(
                $items,
                $totalCount,
                $this->perPage,
                $this->page,
                [
                    'path' => Request::url(),
                    'query' => Request::query(),
                ]
            );
        }
        return new Collection($items);
    }
}
