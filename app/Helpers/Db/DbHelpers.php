<?php

namespace App\Helpers\Db;

use Illuminate\Database\Eloquent\Collection;

class DbHelpers
{
    private array $errorIds = [];

    public static function pluckByColumn(Collection $collection, string $key): array
    {
        return $collection->pluck($key)->toArray();
    }
    public static function getModelClassName(string $modelClass): string
    {
        return str_replace('App\\Models\\', '', $modelClass);
    }
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
        foreach ($ids as $id) {

            if (in_array($id, $results['attached'])) {
                continue;
            }
            if (in_array($id, $results['detached'])) {
                continue;
            }
            if (in_array($id, $results['updated'])) {
                continue;
            }
            $this->errorIds[] = $id;
        }
        return (count($this->errorIds) === 0);
    }
    public function validateDetach(array $results, array $ids)
    {
        $errorIds = [];
        foreach ($ids as $id) {
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
