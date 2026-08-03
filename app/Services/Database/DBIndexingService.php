<?php

namespace App\Services\Database;

use MongoDB\Collection;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBQuery;

class DBIndexingService
{
    public function __construct(
        private MongoDBQuery $mongoDBQuery,
    ) {}

    /**
     * Helper to check if a collection exists in MongoDB.
     */
    public function collectionExists(string $collectionName): bool
    {
        return $this->mongoDBQuery->collectionExists($collectionName);
    }

    /**
     * Helper to check if a specific index exists on a collection by name.
     */
    public function hasIndex(Collection $collection, string $indexName): bool
    {
        foreach ($collection->listIndexes() as $indexInfo) {
            if ($indexInfo->getName() === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a collection has an index matching the given keys specification.
     */
    public function hasIndexWithKeys(Collection $collection, array $targetKeys): bool
    {
        foreach ($collection->listIndexes() as $indexInfo) {
            $existingKey = iterator_to_array($indexInfo->getKey());

            if ($existingKey === $targetKeys) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of an existing index by its key specification.
     */
    public function getIndexNameByKeys(Collection $collection, array $targetKeys): ?string
    {
        foreach ($collection->listIndexes() as $indexInfo) {
            $existingKey = iterator_to_array($indexInfo->getKey());

            if ($existingKey === $targetKeys) {
                return $indexInfo->getName();
            }
        }

        return null;
    }

    /**
     * Helper to retrieve formatted index metadata array for a given collection.
     */
    public function getCollectionIndexesData(S $service, string $collectionName): array
    {
        $mongoDb = $this->mongoDBQuery->getMongoDatabase();
        $indexes = [];

        try {
            $rawCollection = $mongoDb->selectCollection($collectionName);
            $collectionIndexes = $rawCollection->listIndexes();

            foreach ($collectionIndexes as $indexInfo) {
                $indexes[] = [
                    's_id' => $service->id,
                    'name' => $indexInfo->getName(),
                    'key' => iterator_to_array($indexInfo->getKey()),
                    'unique' => $indexInfo->isUnique(),
                ];
            }
        } catch (\Throwable $e) {
            logger()->error(sprintf('Failed to fetch indexes for collection %s: %s', $collectionName, $e->getMessage()));
        }

        return $indexes;
    }

    /**
     * Helper method to build index_data array across all SrTypes for a given Service.
     */
    public function buildServiceIndexData(S $service, array $existingCollections): array
    {
        $indexData = [];

        foreach (SrType::cases() as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);
            $exists = in_array($collectionName, $existingCollections, true);

            $indexData[] = [
                's_id' => $service->id,
                'sr_type' => $srTypeEnum->value,
                'collection' => $collectionName,
                'exists' => $exists,
                'indexes' => $exists ? $this->getCollectionIndexesData($service, $collectionName) : [],
            ];
        }

        return $indexData;
    }

    /**
     * Verify if a given collection name matches a valid SrType for the service.
     */
    public function isServiceCollection(S $service, string $collection): bool
    {
        foreach (SrType::cases() as $srTypeEnum) {
            if ($collection === sprintf('%s_%s', $service->name, $srTypeEnum->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper to get target SrTypes for store/update/destroy operations.
     */
    public function getTargetSrTypes(?string $targetSrType): array
    {
        $srTypes = $targetSrType
            ? [SrType::tryFrom($targetSrType)]
            : SrType::cases();

        return array_filter($srTypes);
    }
}
