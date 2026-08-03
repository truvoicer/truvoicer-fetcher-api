<?php

namespace Tests\Feature\Backend\Tools\Database;

use Database\Seeders\PropertySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\S as Service;
use Truvoicer\TfDbReadCore\Models\SanctumUser;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBQuery;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrOperationsService;

class DatabaseIndexControllerTest extends TestCase
{
    private User $superUser;

    private MongoDBRepository $mongoDbRepository;

    private MongoDBQuery $mongoDbQuery;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tf_mysql' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]]);
        config(['database.default' => 'sqlite']);

        $this->artisan('migrate:fresh', [
            '--database' => 'tf_mysql',
            '--path' => 'database/migrations',
            '--seed' => false,
            '--force' => true,
        ]);

        if (app()->environment() !== 'testing') {
            throw new \Exception('Database cleanup is only allowed in the testing environment.');
        }

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            PropertySeeder::class,
        ]);

        $this->superUser = SanctumUser::first();
        $this->mongoDbRepository = app(MongoDBRepository::class);
        $this->mongoDbQuery = app(MongoDBQuery::class);

        $databaseName = DB::connection('mongodb')->getDatabaseName();
        $this->mongoDbRepository->getMongoDBQuery()
            ->getConnection()
            ->getClient()
            ->dropDatabase($databaseName);
    }

    public function test_index_returns_paginated_services_list(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        Service::factory()->count(3)->create();

        $response = $this->getJson(route('backend.tools.database.indexes.index'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'services' => [
                        '*' => ['id', 'name', 'label'],
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data.services')));
    }

    public function test_show_returns_service_index_data(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();

        // Create one existing collection for this service to test the 'exists' flag
        $existingCollection = sprintf('%s_%s', $service->name, SrType::LIST->value);
        $this->mongoDbQuery->createCollectionIfNotExists($existingCollection);

        $response = $this->getJson(route('backend.tools.database.indexes.show', ['service' => $service->id]));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $service->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'index_data' => [
                        '*' => ['s_id', 'sr_type', 'collection', 'exists', 'indexes'],
                    ],
                ],
            ]);

        $indexData = collect($response->json('data.index_data'));

        // Assert that LIST collection reflects exists = true
        $listCollectionData = $indexData->firstWhere('sr_type', SrType::LIST->value);
        $this->assertTrue($listCollectionData['exists']);
        $this->assertNotEmpty($listCollectionData['indexes']);

        // Assert another collection (e.g. SINGLE) reflects exists = false
        $itemCollectionData = $indexData->firstWhere('sr_type', SrType::SINGLE->value);
        $this->assertFalse($itemCollectionData['exists']);
        $this->assertEmpty($itemCollectionData['indexes']);
    }

    public function test_create_collection_creates_new_mongodb_collection(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->assertFalse($this->mongoDbQuery->collectionExists($collectionName));

        $payload = ['sr_type' => SrType::LIST->value];

        $response = $this->postJson(
            route('backend.tools.database.indexes.create-collection', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', sprintf('Collection [%s] created successfully.', $collectionName));

        // Directly verify collection exists in MongoDB
        $this->assertTrue($this->mongoDbQuery->collectionExists($collectionName));
    }

    public function test_create_collection_returns_conflict_when_collection_already_exists(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        // Pre-create collection
        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $payload = ['sr_type' => SrType::LIST->value];

        $response = $this->postJson(
            route('backend.tools.database.indexes.create-collection', ['service' => $service->id]),
            $payload
        );

        $response->assertStatus(409)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', sprintf('Collection [%s] already exists.', $collectionName));
    }

    public function test_create_default_indexes_successfully(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        // Ensure collection exists first
        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $payload = ['sr_type' => SrType::LIST->value];

        $response = $this->postJson(
            route('backend.tools.database.indexes.create-default-indexes', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $defaultIndexes = SrOperationsService::DEFAULT_MONGODB_INDEXES;
        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);

        $existingIndexKeys = [];
        foreach ($rawCollection->listIndexes() as $indexInfo) {
            $existingIndexKeys[] = iterator_to_array($indexInfo->getKey());
        }

        // Verify that every defined key pattern exists on the collection
        foreach ($defaultIndexes as $indexName => $keyPattern) {
            $this->assertContains(
                $keyPattern,
                $existingIndexKeys,
                sprintf('Failed asserting that default index [%s] was created on [%s].', $indexName, $collectionName)
            );
        }

        // Total index count: 5 custom default indexes + default MongoDB '_id_' index = 6
        $this->assertCount(count($defaultIndexes) + 1, $existingIndexKeys);
    }

    public function test_collection_idx_index_lists_indexes_for_valid_collection(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $response = $this->getJson(route('backend.tools.database.indexes.collections.indexes', [
            'service' => $service->id,
            'collection' => $collectionName,
        ]));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'indexes' => [
                        '*' => ['s_id', 'name', 'key', 'unique'],
                    ],
                ],
            ]);

        // Default collection should contain at least the default '_id_' index
        $indexNames = collect($response->json('data.indexes'))->pluck('name')->toArray();
        $this->assertContains('_id_', $indexNames);
    }

    public function test_collection_idx_index_returns_404_if_collection_does_not_exist(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $response = $this->getJson(route('backend.tools.database.indexes.collections.indexes', [
            'service' => $service->id,
            'collection' => $collectionName,
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    public function test_collection_idx_show_returns_specific_index_by_name(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $response = $this->getJson(route('backend.tools.database.indexes.collections.indexes.show', [
            'service' => $service->id,
            'collection' => $collectionName,
            'indexName' => '_id_',
        ]));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.index.name', '_id_')
            ->assertJsonPath('data.index.key', ['_id' => 1]);
    }

    public function test_collection_idx_show_returns_404_for_non_existent_index(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $response = $this->getJson(route('backend.tools.database.indexes.collections.indexes.show', [
            'service' => $service->id,
            'collection' => $collectionName,
            'indexName' => 'non_existent_index',
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    public function test_store_creates_custom_index(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $payload = [
            'sr_type' => SrType::LIST->value,
            'keys' => ['item_id' => 1],
            'options' => ['name' => 'custom_item_id_1'],
        ];

        $response = $this->postJson(
            route('backend.tools.database.indexes.store', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.results.0.status', 'created')
            ->assertJsonPath('data.results.0.index_name', 'custom_item_id_1');

        // Directly verify in MongoDB that the index exists with exact options
        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);

        $hasIndex = false;
        foreach ($rawCollection->listIndexes() as $indexInfo) {
            if ($indexInfo->getName() === 'custom_item_id_1' && iterator_to_array($indexInfo->getKey()) === ['item_id' => 1]) {
                $hasIndex = true;
                break;
            }
        }

        $this->assertTrue($hasIndex, 'Failed asserting that custom index was physically created in MongoDB.');
    }

    public function test_store_returns_already_exists_status_if_duplicate_index_requested(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        // Pre-create index
        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);
        $rawCollection->createIndex(['item_id' => 1], ['name' => 'custom_item_id_1']);

        $payload = [
            'sr_type' => SrType::LIST->value,
            'keys' => ['item_id' => 1],
            'options' => ['name' => 'custom_item_id_1'],
        ];

        $response = $this->postJson(
            route('backend.tools.database.indexes.store', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.results.0.status', 'already_exists');
    }

    public function test_update_recreates_existing_index(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        // Pre-create old index
        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);
        $rawCollection->createIndex(['item_id' => 1], ['name' => 'old_idx']);

        $payload = [
            'sr_type' => SrType::LIST->value,
            'index_name' => 'old_idx',
            'keys' => ['item_id' => -1],
            'options' => ['name' => 'new_idx'],
        ];

        $response = $this->putJson(
            route('backend.tools.database.indexes.update', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.results.0.status', 'updated')
            ->assertJsonPath('data.results.0.dropped_index', 'old_idx')
            ->assertJsonPath('data.results.0.new_index_name', 'new_idx');

        // Verify old index is gone and new index exists with descending key direction (-1)
        $existingIndexNames = [];
        $newIndexKey = null;

        foreach ($rawCollection->listIndexes() as $indexInfo) {
            $existingIndexNames[] = $indexInfo->getName();
            if ($indexInfo->getName() === 'new_idx') {
                $newIndexKey = iterator_to_array($indexInfo->getKey());
            }
        }

        $this->assertNotContains('old_idx', $existingIndexNames);
        $this->assertContains('new_idx', $existingIndexNames);
        $this->assertEquals(['item_id' => -1], $newIndexKey);
    }

    public function test_destroy_drops_index_successfully(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();
        $collectionName = sprintf('%s_%s', $service->name, SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        // Pre-create index to delete
        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);
        $rawCollection->createIndex(['item_id' => 1], ['name' => 'to_delete_idx']);

        $payload = [
            'sr_type' => SrType::LIST->value,
            'index_name' => 'to_delete_idx',
        ];

        $response = $this->deleteJson(
            route('backend.tools.database.indexes.delete', ['service' => $service->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.results.0.status', 'deleted')
            ->assertJsonPath('data.results.0.dropped_index', 'to_delete_idx');

        // Verify index is physically removed from MongoDB
        $existingIndexNames = [];
        foreach ($rawCollection->listIndexes() as $indexInfo) {
            $existingIndexNames[] = $indexInfo->getName();
        }

        $this->assertNotContains('to_delete_idx', $existingIndexNames);
    }

    public function test_destroy_prevents_dropping_default_id_index(): void
    {
        Sanctum::actingAs($this->superUser, ['*']);
        $service = Service::factory()->create();

        $payload = [
            'sr_type' => SrType::LIST->value,
            'index_name' => '_id_',
        ];

        $response = $this->deleteJson(
            route('backend.tools.database.indexes.delete', ['service' => $service->id]),
            $payload
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot drop default _id_ index.');
    }
}
