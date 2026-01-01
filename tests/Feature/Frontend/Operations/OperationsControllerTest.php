<?php

namespace Tests\Feature\Frontend\Operations;

use Truvoicer\TruFetcherGet\Enums\Api\ApiListKey;
use Truvoicer\TruFetcherGet\Enums\Api\ApiMethod;
use Truvoicer\TruFetcherGet\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use Truvoicer\TruFetcherGet\Enums\Property\PropertyType;
use Truvoicer\TruFetcherGet\Enums\Sr\SrType;
use Truvoicer\TruFetcherGet\Events\ProcessSrOperationDataEvent;
use App\Jobs\ProcessSrOperationData;
use Truvoicer\TruFetcherGet\Models\Category;
use Truvoicer\TruFetcherGet\Models\Property;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\S;
use Truvoicer\TruFetcherGet\Models\Sr;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TruFetcherGet\Services\ApiManager\Operations\DataHandler\ApiRequestMongoDbHandler;
use Database\Seeders\PropertySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\DeepSeekData;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\DefaultGetData;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\GeminiData;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\GrokData;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\OpenAiData;
use Tests\Feature\Frontend\Operations\Data\ApiDirect\DefaultPostData;
use Tests\Feature\Frontend\Operations\Data\Helpers\OperationsDbHelpers;
use Tests\TestCase;

class OperationsControllerTest extends TestCase
{
    private User $superUser;
    private MongoDBRepository $mongoDbRepository;
    private OperationsDbHelpers $operationsDbHelpers;


    protected function setUp(): void
    {
        parent::setUp();

        // CRITICAL: Only run this in the testing environment
        if (app()->environment() !== 'testing') {
            throw new \Exception('Database cleanup is only allowed in the testing environment.');
        }

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $this->superUser = User::first();
        $this->mongoDbRepository = app(MongoDBRepository::class);

        $databaseName = DB::connection('mongodb')->getDatabaseName();
        $this->mongoDbRepository->getMongoDBQuery()
            ->getConnection()
            ->getMongoClient()
            ->dropDatabase($databaseName);

        $this->operationsDbHelpers = OperationsDbHelpers::instance();
    }

    public static function providerPropertyProvider(): array
    {
        return [
            ...DefaultPostData::data(),
            ...DefaultGetData::data(),
            ...DeepSeekData::data(),
            ...GrokData::data(),
            ...OpenAiData::data(),
            ...GeminiData::data(),
        ];
    }

    #[DataProvider('providerPropertyProvider')]
    public function test_list_api_direct_search_operation(
        array $srData,
        array $properties,
        array $srConfigs,
        array $srResponseKeys,
        array $requestResponse,
        array $responseData,
        array $afterResponseData,
        ?array $mocks = null,
        ?array $partialMocks = null,
        ?callable $callback = null
    ): void {

        $this->seed([
            PropertySeeder::class
        ]);

        Sanctum::actingAs(
            $this->superUser,
            ['*']
        );
        $s = S::factory()->create();
        $category = Category::factory()->create();

        $provider = Provider::factory()
            ->has(
                Sr::factory()->state([
                    's_id' => $s->id,
                    'category_id' => $category->id,
                    'type' => SrType::LIST->value,
                    'default_sr' => true,
                    ApiListKey::LIST_KEY->value => (!empty($srData[ApiListKey::LIST_KEY->value]))
                        ? $srData[ApiListKey::LIST_KEY->value]
                        : null
                ])
            )->create();

        $provider->each(function (Provider $provider) use ($properties, $srConfigs, $srResponseKeys) {
            foreach ($properties as $property) {
                $findProperty = Property::where('name', $property['name'])->first();
                unset($property['name']);
                $provider->properties()->attach(
                    $findProperty->id,
                    $property
                );
            }
            $provider->sr->each(function (Sr $sr) use (
                $findProperty,
                $srConfigs,
                $srResponseKeys
            ) {
                foreach ($srConfigs as $srConfig) {
                    $findProperty = Property::where('name', $srConfig['name'])->first();
                    $data = ['property_id' => $findProperty->id];
                    if (array_key_exists('value', $srConfig)) {
                        $data['value'] = $srConfig['value'];
                    } elseif (array_key_exists('big_text_value', $srConfig)) {
                        $data['big_text_value'] = $srConfig['big_text_value'];
                    } elseif (array_key_exists('array_value', $srConfig)) {
                        $data['array_value'] = $srConfig['array_value'];
                    }
                    $sr->srConfig()->create($data);
                }

                foreach ($srResponseKeys as $srResponseKey) {
                    $s = $sr->s;

                    $sResponseKey = $s->sResponseKeys()->create([
                        's_id' => $s->id,
                        'name' => $srResponseKey['name']
                    ]);

                    $srResponseKeyData = [
                        's_response_key_id' => $sResponseKey->id,
                    ];

                    if (array_key_exists('value', $srResponseKey)) {
                        $srResponseKeyData['value'] = $srResponseKey['value'];
                    }
                    if (array_key_exists('show_in_response', $srResponseKey)) {
                        $srResponseKeyData['show_in_response'] = $srResponseKey['show_in_response'];
                    }
                    if (array_key_exists('list_item', $srResponseKey)) {
                        $srResponseKeyData['list_item'] = $srResponseKey['list_item'];
                    }
                    if (array_key_exists('custom_value', $srResponseKey)) {
                        $srResponseKeyData['custom_value'] = $srResponseKey['custom_value'];
                    }
                    if (array_key_exists('search_priority', $srResponseKey)) {
                        $srResponseKeyData['search_priority'] = $srResponseKey['search_priority'];
                    }
                    if (array_key_exists('searchable', $srResponseKey)) {
                        $srResponseKeyData['searchable'] = $srResponseKey['searchable'];
                    }
                    if (array_key_exists('is_date', $srResponseKey)) {
                        $srResponseKeyData['is_date'] = $srResponseKey['is_date'];
                    }
                    if (array_key_exists('date_format', $srResponseKey)) {
                        $srResponseKeyData['date_format'] = $srResponseKey['date_format'];
                    }
                    if (array_key_exists('append_extra_data_value', $srResponseKey)) {
                        $srResponseKeyData['append_extra_data_value'] = $srResponseKey['append_extra_data_value'];
                    }
                    if (array_key_exists('prepend_extra_data_value', $srResponseKey)) {
                        $srResponseKeyData['prepend_extra_data_value'] = $srResponseKey['prepend_extra_data_value'];
                    }
                    if (array_key_exists('array_keys', $srResponseKey)) {
                        $srResponseKeyData['array_keys'] = $srResponseKey['array_keys'];
                    }
                    $srRKey = $sr->srResponseKey()
                        ->create($srResponseKeyData);
                }
            });
        });

        if (is_array($partialMocks)) {
            foreach ($partialMocks as $partialMock) {
                $this->partialMock(
                    $partialMock['class'],
                    $partialMock['function']
                );
            }
        }
        if (is_array($mocks)) {
            foreach ($mocks as $mock) {
                $this->mock(
                    $mock['class'],
                    $mock['function']
                );
            }
        }

        $postData = [
            "page_id" => 1,
            "api_fetch_type" => "api_direct",
            "date_key" => "created_at",
            "page_number" => 1,
            "page_size" => 10,
            "service" => $s->name,
            "sort_by" => "created_at",
            "sort_order" => "desc",
        ];

        Http::fake([
            '*' => Http::response($requestResponse, 200),
        ]);

        $response = $this->post(
            route('front.operation.search', ['type' => 'list']),
            $postData
        );

        if ($callback) {
            $callback($response, $this);
        }
        if (array_key_exists('status', $afterResponseData)) {
            $response->assertStatus($afterResponseData['status']);
        }
        if (!empty($afterResponseData['exception'])) {

            // Retrieve the exception from the response object
            $exception = $response->exception;

            $this->assertInstanceOf(
                $afterResponseData['exception'],
                $exception
            );
            $this->assertEquals(
                $afterResponseData['message'],
                $exception->getMessage()
            );
        }
        if (!empty($responseData)) {

            $response->assertJson([
                'results' => array_map(function (array $item) use ($provider, $category) {
                    $sr = $provider->srs->first();
                    $s = $sr->s;
                    return array_merge(
                        $item,
                        [
                            "provider" => $provider->name,
                            "requestCategory" => $category->name,
                            "serviceRequest" => $sr->name,
                            "service" =>  [
                                "id" => $s->id,
                                "name" => $s->name
                            ]
                        ]
                    );
                }, $responseData)
            ]);
        }
    }


    public function test_database_list_search_operation(): void
    {
        $this->seed([
            PropertySeeder::class
        ]);

        Sanctum::actingAs(
            $this->superUser,
            ['*']
        );

        $s = S::factory()->create();
        $category = Category::factory()->create();
        $provider = Provider::factory()
            ->has(
                Sr::factory()->state([
                    's_id' => $s->id,
                    'category_id' => $category->id,
                    'type' => SrType::LIST->value,
                    'default_sr' => true,
                    ApiListKey::LIST_KEY->value => 'results'
                ])
            )
            ->create();

        $srResponseKeys = [
            [
                'name' => 'id',
                'value' => 'id',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'name',
                'value' => 'name',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'title',
                'value' => 'title',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'description',
                'value' => 'description',
                'show_in_response' => true,
                'list_item' => true,
            ],
        ];
        $properties = [
            [
                'name' => PropertyType::ACCESS_TOKEN->value,
                'value' => '12345'
            ],
            [
                'name' => PropertyType::API_TYPE->value,
                'value' => ApiType::DEFAULT->value
            ],
            [
                'name' => PropertyType::BASE_URL->value,
                'value' => 'http://aurl.com/v1'
            ],
            [
                'name' => PropertyType::RESPONSE_FORMAT->value,
                'value' => ApiResponseFormat::JSON->value
            ],
            [
                'name' => PropertyType::METHOD->value,
                'value' => ApiMethod::GET->value
            ],
        ];
        $srConfigs = [
            [
                'name' => PropertyType::ENDPOINT->value,
                'value' => '/test-endpoint-1'
            ],
            [
                'name' => PropertyType::QUERY->value,
                'array_value' => [
                    'sort' => 'title',
                    'direction' => 'asc',
                ],
            ],
        ];
        $responseData = [
            [
                'name' => 'test-name',
                'title' => 'Test Title',
                'description' => 'This is a test description for test title'
            ],
            [
                'name' => 'test-name-2',
                'title' => 'Test Title 2',
                'description' => 'This is a test description for test title 2'
            ],
            [
                'name' => 'test-name-3',
                'title' => 'Test Title 3',
                'description' => 'This is a test description for test title 3'
            ],
        ];

        $this->operationsDbHelpers->dataInit(
            $provider,
            $srResponseKeys,
            $s,
            $category,
            $properties,
            $srConfigs,
            $responseData,
            count($responseData)
        );

        $requestResponse = [
            'results' => $responseData
        ];

        Http::fake([
            '*' => Http::response($requestResponse, 200),
        ]);


        $postData = [
            "page_id" => 1,
            "api_fetch_type" => "database",
            "date_key" => "created_at",
            "page_number" => 1,
            "page_size" => 10,
            "service" => $s->name,
            "sort_by" => "created_at",
            "sort_order" => "desc",
        ];

        $response = $this->post(
            route('front.operation.search', ['type' => 'list']),
            $postData
        )
            ->assertStatus(200)
            ->assertJsonCount(3, 'results');

        $resultData = json_decode($response->baseResponse->getContent(), true)['results'];

        // For arrays with different key order
        $normalizedExpected = array_map(function ($item) {
            ksort($item);
            return $item;
        }, $responseData);

        $normalizedActual = array_map(function ($item) use ($responseData) {
            ksort($item);
            return array_filter($item, function ($key) use ($responseData) {
                $responseDataItemKeys = array_keys(array_first($responseData));
                return in_array($key, $responseDataItemKeys);
            }, ARRAY_FILTER_USE_KEY);
        }, $resultData);

        $this->assertEqualsCanonicalizing($normalizedExpected, $normalizedActual);
    }

    public function test_database_list_search_operation_api_direct_fallback(): void
    {
        $this->seed([
            PropertySeeder::class
        ]);

        list(
            $normalizedExpected,
            $normalizedActual,
            $responseData
        ) = $this->sharedPreparation();
    }


    public function test_list_search_operation_can_dispatch_save_data_event(): void
    {

        Event::fake([
            ProcessSrOperationDataEvent::class,
        ]);

        $this->seed([
            PropertySeeder::class
        ]);

        list(
            $normalizedExpected,
            $normalizedActual,
            $responseData
        ) = $this->sharedPreparation();

        Event::assertDispatched(ProcessSrOperationDataEvent::class, function (ProcessSrOperationDataEvent $event) use ($normalizedExpected, $responseData) {
            $this->assertEquals($this->superUser->id, $event->userId);
            $this->assertEquals(Sr::first()->id, $event->srId);

            $normalizedActual = array_map(function ($item) use ($responseData) {
                ksort($item);
                return array_filter($item, function ($key) use ($responseData) {
                    $responseDataItemKeys = array_keys(array_first($responseData));
                    return in_array($key, $responseDataItemKeys);
                }, ARRAY_FILTER_USE_KEY);
            }, $event->apiResponse->getRequestData());

            $this->assertEqualsCanonicalizing($normalizedExpected, $normalizedActual);
            return true;
        });
    }

    public function test_list_search_operation_can_queue_save_data_event_job(): void
    {

        Queue::fake([
            ProcessSrOperationData::class,
        ]);

        $this->seed([
            PropertySeeder::class
        ]);

        list(
            $normalizedExpected,
            $normalizedActual,
            $responseData
        ) = $this->sharedPreparation();

        Queue::assertPushed(ProcessSrOperationData::class, function (ProcessSrOperationData $job) use ($normalizedExpected, $responseData) {

            $this->assertEquals(S::first()->id, $job->apiResponse->service['id']);
            $this->assertEquals(Sr::first()->id, $job->apiResponse->serviceRequest['id']);
            $normalizedActual = array_map(function ($item) use ($responseData) {
                ksort($item);
                return array_filter($item, function ($key) use ($responseData) {
                    $responseDataItemKeys = array_keys(array_first($responseData));
                    return in_array($key, $responseDataItemKeys);
                }, ARRAY_FILTER_USE_KEY);
            }, $job->apiResponse->getRequestData());

            $this->assertEqualsCanonicalizing($normalizedExpected, $normalizedActual);
            return true;
        });
    }

    public function sharedPreparation()
    {
        Sanctum::actingAs(
            $this->superUser,
            ['*']
        );

        $s = S::factory()->create();
        $category = Category::factory()->create();
        $provider = Provider::factory()
            ->has(
                Sr::factory()->state([
                    's_id' => $s->id,
                    'category_id' => $category->id,
                    'type' => SrType::LIST->value,
                    'default_sr' => true,
                    ApiListKey::LIST_KEY->value => 'results'
                ])
            )
            ->create();

        $srResponseKeys = [
            [
                'name' => 'id',
                'value' => 'id',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'name',
                'value' => 'name',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'title',
                'value' => 'title',
                'show_in_response' => true,
                'list_item' => true,
            ],
            [
                'name' => 'description',
                'value' => 'description',
                'show_in_response' => true,
                'list_item' => true,
            ],
        ];
        $properties = [
            [
                'name' => PropertyType::ACCESS_TOKEN->value,
                'value' => '12345'
            ],
            [
                'name' => PropertyType::API_TYPE->value,
                'value' => ApiType::DEFAULT->value
            ],
            [
                'name' => PropertyType::BASE_URL->value,
                'value' => 'http://aurl.com/v1'
            ],
            [
                'name' => PropertyType::RESPONSE_FORMAT->value,
                'value' => ApiResponseFormat::JSON->value
            ],
            [
                'name' => PropertyType::METHOD->value,
                'value' => ApiMethod::GET->value
            ],
        ];
        $srConfigs = [
            [
                'name' => PropertyType::ENDPOINT->value,
                'value' => '/test-endpoint-1'
            ],
            [
                'name' => PropertyType::QUERY->value,
                'array_value' => [
                    'sort' => 'title',
                    'direction' => 'asc',
                ],
            ],
        ];
        $responseData = [
            [
                'id' => 1,
                'name' => 'test-name',
                'title' => 'Test Title',
                'description' => 'This is a test description for test title'
            ],
            [
                'id' => 2,
                'name' => 'test-name-2',
                'title' => 'Test Title 2',
                'description' => 'This is a test description for test title 2'
            ],
            [
                'id' => 3,
                'name' => 'test-name-3',
                'title' => 'Test Title 3',
                'description' => 'This is a test description for test title 3'
            ],
        ];

        $this->operationsDbHelpers->dataInit(
            $provider,
            $srResponseKeys,
            $s,
            $category,
            $properties,
            $srConfigs,
            [],
            0
        );

        // First create a real instance
        $handler = app(ApiRequestMongoDbHandler::class);

        // Then mock it
        $this->instance(
            ApiRequestMongoDbHandler::class,
            Mockery::mock($handler, function (MockInterface $mock) {
                $mock->shouldReceive('searchOperation')
                    ->andReturn(null);
            })
        );

        $requestResponse = [
            'results' => $responseData
        ];

        Http::fake([
            '*' => Http::response($requestResponse, 200),
        ]);

        $postData = [
            "page_id" => 1,
            "api_fetch_type" => "api_direct",
            "date_key" => "created_at",
            "page_number" => 1,
            "page_size" => 10,
            "service" => $s->name,
            "sort_by" => "created_at",
            "sort_order" => "desc",
        ];

        $response = $this->post(
            route('front.operation.search', ['type' => 'list']),
            $postData
        )
            ->assertStatus(200)
            ->assertJsonCount(3, 'results');


        $resultData = json_decode($response->baseResponse->getContent(), true)['results'];

        // For arrays with different key order
        $normalizedExpected = array_map(function ($item) {
            ksort($item);
            return $item;
        }, $responseData);

        $normalizedActual = array_map(function ($item) use ($responseData) {
            ksort($item);
            return array_filter($item, function ($key) use ($responseData) {
                $responseDataItemKeys = array_keys(array_first($responseData));
                return in_array($key, $responseDataItemKeys);
            }, ARRAY_FILTER_USE_KEY);
        }, $resultData);

        $this->assertEqualsCanonicalizing($normalizedExpected, $normalizedActual);

        return [
            $normalizedExpected,
            $normalizedActual,
            $responseData
        ];
    }
}
