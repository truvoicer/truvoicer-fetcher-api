<?php

namespace App\Http\Controllers\Api\Backend\Tools\Database;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Database\CreateDefaultDBIndexesRequest;
use App\Http\Requests\Admin\Tools\Database\CreateMongoDBCollectionRequest;
use App\Http\Requests\Admin\Tools\Database\DestroyDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\FetchDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\StoreDatabaseIndexRequest;
use App\Http\Requests\Admin\Tools\Database\UpdateDatabaseIndexRequest;
use App\Http\Resources\Service\SDBCollectionIndexCollection;
use App\Http\Resources\Service\ServiceDatabaseIndexCollection;
use App\Http\Resources\Service\ServiceDatabaseIndexResource;
use App\Services\Database\DBIndexingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Truvoicer\TfDbReadCore\Models\S as Service;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBQuery;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrOperationsService;

/**
 * Contains api endpoint functions for database index operations
 */
class DatabaseIndexController extends Controller
{
    public function __construct(
        private ApiService $apiServicesService,
        private DBIndexingService $dbIndexingService,
        private MongoDBQuery $mongoDBQuery,
        private MongoDBRepository $mongoDBRepository,
    ) {
        parent::__construct();
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

        $existingCollections = $this->mongoDBQuery->getAllCollectionNames();

        $service->setAttribute('index_data', $this->dbIndexingService->buildServiceIndexData($service, $existingCollections));

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
        if (! $this->dbIndexingService->isServiceCollection($service, $collection)) {
            return $this->sendErrorResponse('The requested collection does not belong to this service.', [], [], 422);
        }

        // 2. Verify collection exists in MongoDB
        if (! $this->dbIndexingService->collectionExists($collection)) {
            return $this->sendErrorResponse(
                sprintf('Collection [%s] does not exist in the database.', $collection),
                [],
                [],
                404
            );
        }

        // 3. Get all index entries for the collection
        $allIndexes = $this->dbIndexingService->getCollectionIndexesData($service, $collection);

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
        if (! $this->dbIndexingService->isServiceCollection($service, $collection)) {
            return $this->sendErrorResponse('The requested collection does not belong to this service.', [], [], 422);
        }

        // 2. Verify collection exists in MongoDB
        if (! $this->dbIndexingService->collectionExists($collection)) {
            return $this->sendErrorResponse(
                sprintf('Collection [%s] does not exist in the database.', $collection),
                [],
                [],
                404
            );
        }

        // 3. Get all index entries for the collection
        $allIndexes = $this->dbIndexingService->getCollectionIndexesData($service, $collection);

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
    public function createCollection(Service $service, CreateMongoDBCollectionRequest $request): JsonResponse
    {
        $targetSrType = $request->input('sr_type');

        $collectionName = $this->mongoDBRepository->getCollectionNameByService($service, $targetSrType);

        try {

            if ($this->mongoDBQuery->collectionExists($collectionName)) {
                return $this->sendErrorResponse(
                    sprintf('Collection [%s] already exists.', $collectionName),
                    [],
                    [],
                    409
                );
            }
            $this->mongoDBQuery->createCollectionIfNotExists($collectionName);
        } catch (\Throwable $e) {
            return $this->sendErrorResponse(
                sprintf('Failed to create collection [%s].', $collectionName),
                [],
                [],
                500
            );
        }

        return $this->sendSuccessResponse(
            sprintf('Collection [%s] created successfully.', $collectionName),
            []
        );
    }

    public function createDefaultIndexes(Service $service, CreateDefaultDBIndexesRequest $request): JsonResponse
    {
        $targetSrType = $request->input('sr_type');

        $collectionName = $this->mongoDBRepository->getCollectionNameByService($service, $targetSrType);

        try {
            if (! $this->mongoDBQuery->collectionExists($collectionName)) {
                return $this->sendErrorResponse(
                    sprintf('Collection [%s] not found.', $collectionName),
                    [],
                    [],
                    404
                );
            }
            $this->mongoDBQuery->setCollection($collectionName)
                ->ensureCollectionIndexes(
                    SrOperationsService::DEFAULT_MONGODB_INDEXES
                );
        } catch (\Throwable $e) {
            return $this->sendErrorResponse(
                sprintf('Failed to create default indexes for collection [%s].', $collectionName),
                [],
                [],
                500
            );
        }

        return $this->sendSuccessResponse(
            sprintf('Default indexes created successfully for collection [%s].', $collectionName),
            []
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

        $mongoDb = $this->mongoDBQuery->getMongoDatabase();

        $processedCollections = [];
        $srTypes = $this->dbIndexingService->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->dbIndexingService->collectionExists($collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);

                    $expectedIndexName = $options['name'] ?? null;

                    if ($expectedIndexName && $this->dbIndexingService->hasIndex($rawCollection, $expectedIndexName)) {
                        $processedCollections[] = [
                            'collection' => $collectionName,
                            'sr_type' => $srTypeEnum->value,
                            'index_name' => $expectedIndexName,
                            'status' => 'already_exists',
                        ];

                        continue;
                    }

                    if ($this->dbIndexingService->hasIndexWithKeys($rawCollection, $keys)) {
                        $processedCollections[] = [
                            'collection' => $collectionName,
                            'sr_type' => $srTypeEnum->value,
                            'index_name' => $this->dbIndexingService->getIndexNameByKeys($rawCollection, $keys),
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

        $mongoDb = $this->mongoDBQuery->getMongoDatabase();

        $processedCollections = [];
        $srTypes = $this->dbIndexingService->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->dbIndexingService->collectionExists($collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);
                    if ($this->dbIndexingService->hasIndex($rawCollection, $oldIndexName)) {
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

        $mongoDb = $this->mongoDBQuery->getMongoDatabase();

        $processedCollections = [];
        $srTypes = $this->dbIndexingService->getTargetSrTypes($targetSrType);

        foreach ($srTypes as $srTypeEnum) {
            $collectionName = sprintf('%s_%s', $service->name, $srTypeEnum->value);

            if ($this->dbIndexingService->collectionExists($collectionName)) {
                try {
                    $rawCollection = $mongoDb->selectCollection($collectionName);

                    if ($this->dbIndexingService->hasIndex($rawCollection, $indexName)) {
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
}
