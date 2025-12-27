<?php

namespace App\Repositories\MongoDB;

use App\Helpers\Tools\UtilHelpers;

class MongoAggregationBuilder
{
    private array $pipeline = [];
    private array $priorityFields = [];
    private array $switchBranches = [];

    /**
     * Adds filtering, priority scoring, and sorting stages.
     *
     * @param array $mandatoryFields Fields that must match, now with operator support.
     * @param string $mandatoryLogic How to combine mandatory fields ('and' or 'or')
     * @return $this
     */
    public function addPrioritySearch(array $mandatoryFields = [], string $mandatoryLogic = 'and'): self
    {
        $priorityOrConditions = [];
        $switchBranches = [];
        $allMatchConditions = [];
        $group = [];
        // 1. Build conditions for mandatory fields with operator support
        foreach ($mandatoryFields as $field) {

            if (empty($field['column']) || !isset($field['value'])) continue;
            $operator = !empty($field['operator']) ? $field['operator'] : 'and';
            $comparison = $field['comparison'] ?? '='; // Default to equals if not provided

            switch ($comparison) {
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

            $group[] = [
                'field' => $field,
                'operator' => $operator,
                'expr' => $mongoExpr
            ];
        }

        $matchIndex = UtilHelpers::findArrayKeyIndex($this->pipeline, '$match');
        if ($matchIndex === false) {
            $this->pipeline[] = ['$match' => []];
            $matchIndex = 0;
        }
        if (
            !array_key_exists(
                '$' . $mandatoryLogic,
                $this->pipeline[$matchIndex]['$match']
            )
        ) {
            $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic] = [];
        }

        $ors = [];
        $ands = [];

        foreach (
            array_filter($group, function ($item) {
                return $item['operator'] === 'and';
            }, ARRAY_FILTER_USE_BOTH) as $item
        ) {
            $ands[$item['field']['column']] = $item['expr'];
        }
        foreach (
            array_filter($group, function ($item) {
                return $item['operator'] === 'or';
            }, ARRAY_FILTER_USE_BOTH) as $item
        ) {
            $ors[$item['field']['column']] = $item['expr'];
        }

        if (count($ors)) {

            $matchOrIndex = UtilHelpers::findArrayKeyIndex(
                $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic],
                '$or'
            );

            if ($matchOrIndex === false) {
                $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][] = [
                    '$or' => []
                ];
                $matchOrIndex = UtilHelpers::findArrayKeyIndex(
                    $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic],
                    '$or'
                );
            }

            $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][$matchOrIndex]['$or'] = [
                ...$this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][$matchOrIndex]['$or'],
                ...[$ors]
            ];
        }
        if (count($ands)) {
            // dd($ands);
            $matchAndIndex = UtilHelpers::findArrayKeyIndex(
                $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic],
                '$and'
            );

            if ($matchAndIndex === false) {
                $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][] = [
                    '$and' => []
                ];

                $matchAndIndex = UtilHelpers::findArrayKeyIndex(
                    $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic],
                    '$and'
                );
            }
            $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][$matchAndIndex]['$and'] = [
                ...$this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][$matchAndIndex]['$and'],
                ...[$ands]
            ];
        }


        return $this;
    }

    public function buildSwitchBranches(): self
    {
        if (!empty($this->switchBranches)) {
            $this->pipeline[] = ['$addFields' => [
                'priority' => ['$switch' => ['branches' => $this->switchBranches, 'default' => 99]]
            ]];
            $this->pipeline[] = ['$sort' => ['priority' => 1]];
        }
        return $this;
    }

    public function buildPriorityFIelds(string $mandatoryLogic = 'and'): self
    {

        // 2. Build conditions for prioritized fields
        foreach ($this->priorityFields as $index => $field) {
            if (empty($field['column']) || !isset($field['value'])) continue;

            if (is_string($field['value'])) {
                $priorityOrConditions[] = [$field['column'] => ['$regex' => $field['value'], '$options' => 'i']];
                $this->switchBranches[] = [
                    'case' => ['$regexMatch' => [
                        'input' => [
                            '$toString' => [
                                '$ifNull' => [
                                    '$' . $field['column'],
                                    ''
                                ]
                            ],
                        ],
                        'regex' => $field['value'],
                        'options' => 'i'
                    ]],
                    'then' => $index + 1,
                ];
            } elseif (is_numeric($field['value'])) {
                $priorityOrConditions[] = [$field['column'] => $field['value']];
                $this->switchBranches[] = [
                    'case' => ['$eq' => ['$' . $field['column'], $field['value']]],
                    'then' => $index + 1,
                ];
            }
        }

        $matchIndex = UtilHelpers::findArrayKeyIndex($this->pipeline, '$match');
        if ($matchIndex === false) {
            return $this;
        }
        // 3. Combine mandatory and priority conditions
        if (!empty($priorityOrConditions)) {
            $this->pipeline[$matchIndex]['$match']['$' . $mandatoryLogic][] = [
                '$or' => $priorityOrConditions
            ];
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
        if (count($this->priorityFields)) {
            $this->buildPriorityFIelds();
        }
        if (count($this->switchBranches)) {
            $this->buildSwitchBranches();
        }
        return $this->pipeline;
    }

    public function getPriorityFields(): array
    {
        return $this->priorityFields;
    }

    public function setPriorityFields(array $priorityFields): self
    {
        $this->priorityFields = $priorityFields;
        return $this;
    }

    public function getSwitchBranches(): array
    {
        return $this->switchBranches;
    }

    public function setSwitchBranches(array $switchBranches): self
    {
        $this->switchBranches = $switchBranches;
        return $this;
    }
}
