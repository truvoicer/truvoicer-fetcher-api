<?php

namespace App\Http\Controllers\Api\Backend\Tools\Database;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Database\DestroyDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\FetchDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\StoreDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\UpdateDatabaseIndexRequest;
use App\Http\Resources\Service\SDBCollectionIndexCollection;
use App\Http\Resources\Service\ServiceDatabaseIndexCollection;
use App\Http\Resources\Service\ServiceDatabaseIndexResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\S as Service;
use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;

/**
 * Contains api endpoint functions for database index operations
 */
class DatabaseIndexController extends Controller
{
    public function __construct(
        private ApiService $apiServicesService
    ) {
        parent::__construct();
    }

    /**
     * Helper to retrieve formatted index metadata array for a given collection.
     */
    private function getCollectionIndexesData(Service $service, \MongoDB\Database $mongoDb, string $collectionName): array
    {
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
    private function buildServiceIndexData(Service $service, \MongoDB\Database $mongoDb, array $existingCollections): array
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
                'indexes' => $exists ? $this->getCollectionIndexesData($service, $mongoDb, $collectionName) : [],
            ];
        }

        return $indexData;
    }

    /**
     * Verify if a given collection name matches a valid SrType for the service.
     */
    private function isServiceCollection(Service $service, string $collection): bool
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
    private function getTargetSrTypes(?string $targetSrType): array
    {
        $srTypes = $targetSrType
            ? [SrType::tryFrom($targetSrType)]
            : SrType::cases();

        return array_filter($srTypes);
    }

    public function index(FetchDatabaseIndexRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if ($this->accessControlService->inAdminGroup()) {
            $getServices = $this->apiServicesService->findByParams(
                $request->input('sort', 'name'),
                $request->input('order', 'asc'),
                $request->input('count', -1),
                $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
            );
        } else {
            $this->apiServicesService->getServiceRepository()->setOrderDir($request->input('order', 'asc'));
            $this->apiServicesService->getServiceRepository()->setSortField($request->input('sort', 'name'));
            $this->apiServicesService->getServiceRepository()->setLimit($request->input('count', -1));

            $getServices = $this->apiServicesService->findUserServices(
                $request->user(),
                $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return $this->sendSuccessResponse(
            'success',
            new ServiceDatabaseIndexCollection($getServices)
        );
    }

    /**
     * Display index data for a specific service across its collections.
     */
    public function show(Service $service): JsonResponse
    {
        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        $existingCollections = iterator_to_array($mongoDb->listCollectionNames());

        $service->setAttribute('index_data', $this->buildServiceIndexData($service, $mongoDb, $existingCollections));

        return $this->sendSuccessResponse(
            'Service index data retrieved successfully.',
            ServiceDatabaseIndexResource::make($service)
        );
    }

    /**
     * Display a paginated list of indexes for a specific collection of a service.
     */
    public function collectionIdxIndex(Request $request, Service $service, string $collection): JsonResponse
    {
        // 1. Verify the requested collection belongs to the service
        if (! $this->isServiceCollection($service, $collection)) {
            return $this->sendErrorResponse('The requested collection does not belong to this service.', [], [], 422);
        }

        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        // 2. Verify collection exists in MongoDB
        if (! $this->collectionExists($mongoDb, $collection)) {
            return $this->sendErrorResponse(
                sprintf('Collection [%s] does not exist in the database.', $collection),
                [],
                [],
                404
            );
        }

        // 3. Get all index entries for the collection
        $allIndexes = $this->getCollectionIndexesData($service, $mongoDb, $collection);

        // 4. Handle Pagination
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $total = count($allIndexes);

        $offset = ($page - 1) * $perPage;
        $pagedIndexes = array_slice($allIndexes, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $pagedIndexes,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return $this->sendSuccessResponse(
            sprintf('Indexes for collection [%s] retrieved successfully.', $collection),
            SDBCollectionIndexCollection::make($paginator)
        );
    }

    public function collectionIdxShow(Request $request, Service $service, string $collection, string $indexName): JsonResponse
    {
        // 1. Verify the requested collection belongs to the service
        if (! $this->isServiceCollection($service, $collection)) {
            return $this->sendErrorResponse('The requested collection does not belong to this service.', [], [], 422);
        }

        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        // 2. Verify collection exists in MongoDB
        if (! $this->collectionExists($mongoDb, $collection)) {
            return $this->sendErrorResponse(
                sprintf('Collection [%s] does not exist in the database.', $collection),
                [],
                [],
                404
            );
        }

        // 3. Get all index entries for the collection
        $allIndexes = $this->getCollectionIndexesData($service, $mongoDb, $collection);

        // 4. Find the specific index by name
        foreach ($allIndexes as $index) {
            if ($index['name'] === $indexName) {
                return $this->sendSuccessResponse(
                    sprintf('Index [%s] for collection [%s] retrieved successfully.', $indexName, $collection),
                    ['index' => $index]
                );
            }
        }

        return $this->sendErrorResponse(
            sprintf('Index [%s] not found in collection [%s].', $indexName, $collection),
            [],
            [],
            404
        );
    }

    /**
     * Create a new index on a service collection.
     */
    public function store(StoreDatabaseIndexRequest $request, Service $service): JsonResponse
    {
        $keys = $request->input('keys');
        $options = $request->input('options', []);
        $targetSrType = $request->input('sr_type');

        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        $processedCollections = [];
        $srTypes = $this->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->collectionExists($mongoDb, $collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);

                    $expectedIndexName = $options['name'] ?? null;

                    if ($expectedIndexName && $this->hasIndex($rawCollection, $expectedIndexName)) {
                        $processedCollections[] = [
                            'collection' => $collectionName,
                            'sr_type' => $srTypeEnum->value,
                            'index_name' => $expectedIndexName,
                            'status' => 'already_exists',
                        ];

                        continue;
                    }

                    if ($this->hasIndexWithKeys($rawCollection, $keys)) {
                        $processedCollections[] = [
                            'collection' => $collectionName,
                            'sr_type' => $srTypeEnum->value,
                            'index_name' => $this->getIndexNameByKeys($rawCollection, $keys),
                            'status' => 'already_exists',
                        ];

                        continue;
                    }

                    $indexName = $rawCollection->createIndex($keys, $options);

                    $processedCollections[] = [
                        'collection' => $collectionName,
                        'sr_type' => $srTypeEnum->value,
                        'index_name' => $indexName,
                        'status' => 'created',
                    ];
                } catch (\Throwable $e) {
                    return $this->sendErrorResponse(
                        sprintf('Failed to create index on collection %s: %s', $collectionName, $e->getMessage()),
                        [],
                        [],
                        500
                    );
                }
            }
        }

        if (empty($processedCollections)) {
            return $this->sendErrorResponse(
                'No matching existing collections found for this service.',
                [],
                [],
                404
            );
        }

        return $this->sendSuccessResponse('Indexes created successfully.', [
            'results' => $processedCollections,
        ]);
    }

    /**
     * Update an index (Re-create or modify options).
     */
    public function update(UpdateDatabaseIndexRequest $request, Service $service): JsonResponse
    {
        $oldIndexName = $request->input('index_name');
        $keys = $request->input('keys');
        $options = $request->input('options', []);
        $targetSrType = $request->input('sr_type');

        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        $processedCollections = [];
        $srTypes = $this->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->collectionExists($mongoDb, $collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);
                    if ($this->hasIndex($rawCollection, $oldIndexName)) {
                        $rawCollection->dropIndex($oldIndexName);
                    }

                    $newIndexName = $rawCollection->createIndex($keys, $options);

                    $processedCollections[] = [
                        'collection' => $collectionName,
                        'sr_type' => $srTypeEnum->value,
                        'dropped_index' => $oldIndexName,
                        'new_index_name' => $newIndexName,
                        'status' => 'updated',
                    ];
                } catch (\Throwable $e) {
                    return $this->sendErrorResponse(
                        sprintf('Failed to update index on collection %s: %s', $collectionName, $e->getMessage()),
                        [],
                        [],
                        500
                    );
                }
            }
        }

        return $this->sendSuccessResponse('Indexes updated successfully.', [
            'results' => $processedCollections,
        ]);
    }

    /**
     * Drop/Destroy an index from a service collection.
     */
    public function destroy(DestroyDatabaseIndexRequest $request, Service $service): JsonResponse
    {
        $indexName = $request->input('index_name');
        $targetSrType = $request->input('sr_type');

        if ($indexName === '_id_') {
            return $this->sendErrorResponse('Cannot drop default _id_ index.', [], [], 422);
        }

        /** @var \MongoDB\Laravel\Connection $mongoConnection */
        $mongoConnection = DB::connection('mongodb');
        $mongoDb = $mongoConnection->getMongoDB();

        $processedCollections = [];
        $srTypes = $this->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->collectionExists($mongoDb, $collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);

                    if ($this->hasIndex($rawCollection, $indexName)) {
                        $rawCollection->dropIndex($indexName);

                        $processedCollections[] = [
                            'collection' => $collectionName,
                            'sr_type' => $srTypeEnum->value,
                            'dropped_index' => $indexName,
                            'status' => 'deleted',
                        ];
                    }
                } catch (\Throwable $e) {
                    return $this->sendErrorResponse(
                        sprintf('Failed to drop index on collection %s: %s', $collectionName, $e->getMessage()),
                        [],
                        [],
                        500
                    );
                }
            }
        }

        return $this->sendSuccessResponse('Index deleted successfully.', [
            'results' => $processedCollections,
        ]);
    }

    /**
     * Helper to check if a collection exists in MongoDB.
     */
    private function collectionExists(\MongoDB\Database $mongoDb, string $collectionName): bool
    {
        $collections = iterator_to_array($mongoDb->listCollectionNames());

        return in_array($collectionName, $collections, true);
    }

    /**
     * Helper to check if a specific index exists on a collection by name.
     */
    private function hasIndex(\MongoDB\Collection $collection, string $indexName): bool
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
    private function hasIndexWithKeys(\MongoDB\Collection $collection, array $targetKeys): bool
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
    private function getIndexNameByKeys(\MongoDB\Collection $collection, array $targetKeys): ?string
    {
        foreach ($collection->listIndexes() as $indexInfo) {
            $existingKey = iterator_to_array($indexInfo->getKey());

            if ($existingKey === $targetKeys) {
                return $indexInfo->getName();
            }
        }

        return null;
    }
}
