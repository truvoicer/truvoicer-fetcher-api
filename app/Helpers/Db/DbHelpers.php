<?php

namespace App\Helpers\Db;

class DbHelpers
{
    private array $errorIds = [];
    public function validateToggle(array $results, array $ids)
    {
        $errorIds = [];
        foreach ($ids as $id) {
            if (in_array($id, $results['attached'])) {
                continue;
            }
            if (in_array($id, $results['detached'])) {
                continue;
            }
            $this->errorIds[] = $id;
        }
        return (count($this->errorIds) === 0);
    }
    public function validateSync(array $results, array $ids)
    {
        $errorIds = [];
        foreach ($ids as $id) {
            if (in_array($id, $results['attached'])) {
                continue;
            }
            if (in_array($id, $results['detached'])) {
                continue;
            }
            $this->errorIds[] = $id;
        }
        return (count($this->errorIds) === 0);
    }

    public function getErrorIds(): array
    {
        return $this->errorIds;
    }
}
