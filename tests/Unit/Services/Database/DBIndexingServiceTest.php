<?php

namespace Tests\Unit\Services\Database;

use App\Services\Database\DBIndexingService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\S as Service;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBQuery;

class DBIndexingServiceTest extends TestCase
{
    private DBIndexingService $dbIndexingService;

    private MongoDBQuery $mongoDbQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mongoDbQuery = app(MongoDBQuery::class);
        $this->dbIndexingService = app(DBIndexingService::class);

        // Cleanup testing MongoDB database
        $databaseName = DB::connection('mongodb')->getDatabaseName();
        $this->mongoDbQuery->getConnection()->getClient()->dropDatabase($databaseName);
    }

    public function test_collection_exists_returns_correct_boolean(): void
    {
        $collectionName = 'test_collection_exists';

        $this->assertFalse($this->dbIndexingService->collectionExists($collectionName));

        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $this->assertTrue($this->dbIndexingService->collectionExists($collectionName));
    }

    public function test_is_service_collection_validates_collection_naming_convention(): void
    {
        $service = new Service(['name' => 'test_service']);

        $validCollection = sprintf('test_service_%s', SrType::LIST->value);
        $invalidCollection = 'test_service_invalid_type';

        $this->assertTrue($this->dbIndexingService->isServiceCollection($service, $validCollection));
        $this->assertFalse($this->dbIndexingService->isServiceCollection($service, $invalidCollection));
    }

    public function test_get_target_sr_types_returns_single_enum_or_all_cases(): void
    {
        // Single target
        $singleType = $this->dbIndexingService->getTargetSrTypes(SrType::LIST->value);
        $this->assertCount(1, $singleType);
        $this->assertEquals(SrType::LIST, reset($singleType));

        // Null target (should return all cases)
        $allTypes = $this->dbIndexingService->getTargetSrTypes(null);
        $this->assertCount(count(SrType::cases()), $allTypes);
    }

    public function test_has_index_and_has_index_with_keys_helpers(): void
    {
        $collectionName = 'test_index_helpers';
        $this->mongoDbQuery->createCollectionIfNotExists($collectionName);

        $rawCollection = $this->mongoDbQuery->getMongoDatabase()->selectCollection($collectionName);
        $rawCollection->createIndex(['provider' => 1], ['name' => 'provider_1']);

        // Test hasIndex by name
        $this->assertTrue($this->dbIndexingService->hasIndex($rawCollection, 'provider_1'));
        $this->assertFalse($this->dbIndexingService->hasIndex($rawCollection, 'non_existent_idx'));

        // Test hasIndexWithKeys
        $this->assertTrue($this->dbIndexingService->hasIndexWithKeys($rawCollection, ['provider' => 1]));
        $this->assertFalse($this->dbIndexingService->hasIndexWithKeys($rawCollection, ['non_existent' => 1]));

        // Test getIndexNameByKeys
        $this->assertEquals('provider_1', $this->dbIndexingService->getIndexNameByKeys($rawCollection, ['provider' => 1]));
        $this->assertNull($this->dbIndexingService->getIndexNameByKeys($rawCollection, ['non_existent' => 1]));
    }

    public function test_build_service_index_data_formats_all_sr_types(): void
    {
        $service = new Service(['id' => 1, 'name' => 'demo_service']);
        $existingCollection = sprintf('demo_service_%s', SrType::LIST->value);

        $this->mongoDbQuery->createCollectionIfNotExists($existingCollection);
        $existingCollections = $this->mongoDbQuery->getAllCollectionNames();

        $indexData = $this->dbIndexingService->buildServiceIndexData($service, $existingCollections);

        $this->assertCount(count(SrType::cases()), $indexData);

        // Find the record for LIST
        $listData = collect($indexData)->firstWhere('sr_type', SrType::LIST->value);

        $this->assertTrue($listData['exists']);
        $this->assertNotEmpty($listData['indexes']);
    }
}
