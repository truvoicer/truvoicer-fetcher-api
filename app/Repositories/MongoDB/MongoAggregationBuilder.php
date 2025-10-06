<?php

namespace App\Repositories\MongoDB;

class MongoAggregationBuilder
{
    private array $pipeline = [];

    /**
     * Adds filtering, priority scoring, and sorting stages.
     *
     * @param array $priorityFields  Fields for prioritized searching.
     * @param array $mandatoryFields Fields that must match, now with operator support.
     * @return $this
     */
    public function addPrioritySearch(array $priorityFields, array $mandatoryFields = []): self
    {
        $priorityOrConditions = [];
        $switchBranches = [];
        $allMatchConditions = [];

        // 1. Build conditions for mandatory fields with operator support
        foreach ($mandatoryFields as $field) {
            if (empty($field['column']) || !isset($field['value'])) continue;

            $operator = $field['operator'] ?? '='; // Default to equals if not provided

            switch ($operator) {
                case '>':
                    $mongoExpr = ['$gt' => $field['value']];
                    break;
                case '>=':
                    $mongoExpr = ['$gte' => $field['value']];
                    break;
                case '<':
                    $mongoExpr = ['$lt' => $field['value']];
                    break;
                case '<=':
                    $mongoExpr = ['$lte' => $field['value']];
                    break;
                case 'regex':
                    $mongoExpr = ['$regex' => $field['value'], '$options' => 'i'];
                    break;
                case '=':
                default:
                    // Simple equality for numbers, booleans, etc.
                    $mongoExpr = $field['value'];
                    break;
            }
            $allMatchConditions[] = [$field['column'] => $mongoExpr];
        }

        // 2. Build conditions for prioritized fields (this logic is unchanged)
        foreach ($priorityFields as $index => $field) {
            if (empty($field['column']) || !isset($field['value'])) continue;

            if (is_string($field['value'])) {
                $priorityOrConditions[] = [$field['column'] => ['$regex' => $field['value'], '$options' => 'i']];
                $switchBranches[] = [
                    'case' => ['$regexMatch' => ['input' => ['$toString' => '$' . $field['column']], 'regex' => $field['value'], 'options' => 'i']],
                    'then' => $index + 1,
                ];
            } elseif (is_numeric($field['value'])) {
                $priorityOrConditions[] = [$field['column'] => $field['value']];
                $switchBranches[] = [
                    'case' => ['$eq' => ['$' . $field['column'], $field['value']]],
                    'then' => $index + 1,
                ];
            }
        }

        // 3. Combine mandatory and priority conditions
        if (!empty($priorityOrConditions)) {
            $allMatchConditions[] = ['$or' => $priorityOrConditions];
        }

        // 4. Add the stages to the main pipeline
        if (!empty($allMatchConditions)) {
            $matchQuery = count($allMatchConditions) === 1 ? $allMatchConditions[0] : ['$and' => $allMatchConditions];
            $this->pipeline[] = ['$match' => $matchQuery];
        }

        if (!empty($switchBranches)) {
            $this->pipeline[] = ['$addFields' => [
                'priority' => ['$switch' => ['branches' => $switchBranches, 'default' => 99]]
            ]];
            $this->pipeline[] = ['$sort' => ['priority' => 1]];
        }

        return $this;
    }

    public function skip(int $count): self
    {
        $this->pipeline[] = ['$skip' => $count];
        return $this;
    }

    public function limit(int $count): self
    {
        $this->pipeline[] = ['$limit' => $count];
        return $this;
    }

    public function getPipeline(): array
    {
        return $this->pipeline;
    }
}
