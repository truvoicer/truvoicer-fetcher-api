<?php

namespace Tests\Feature\Scheduler;

use Truvoicer\TruFetcherGet\Enums\Api\ApiListKey;
use Truvoicer\TruFetcherGet\Enums\Api\ApiMethod;
use Truvoicer\TruFetcherGet\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use Truvoicer\TruFetcherGet\Enums\Property\PropertyType;
use Truvoicer\TruFetcherGet\Enums\Sr\SrType;
use Truvoicer\TruFetcherGet\Events\RunSrOperationEvent;
use Truvoicer\TruFetcherGet\Models\Category;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\S;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Models\SrSchedule;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\DataHandler\ApiRequestMongoDbHandler;
use App\Services\Provider\ProviderScheduleService;
use Database\Seeders\PropertySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Scheduling\CallbackEvent;
use Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\Frontend\Operations\Data\Helpers\OperationsDbHelpers;

class ProviderScheduleTest extends TestCase
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
            PropertySeeder::class,
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

    public function test_sr_operation_schedules_run_correctly()
    {

        list(
            $provider,
            $category,
            $s
        ) = $this->sharedPreparation();

        $provider->srs->each(function (Sr $sr) {

            $srSchedule = $sr->srSchedule()->create([
                'every_minute' => true,
            ]);
        });
        // Get the scheduler instance from the application container
        $schedule = app(Schedule::class);

        app(ProviderScheduleService::class)
            ->setSchedule($schedule)
            ->run();

        // Get all scheduled events and filter for your job
        $events = collect($schedule->events())->filter(function ($event) {

            // The job class name is often stored in the event's description
            return $event->description === 'Sr operation schedules';
        });

        // Assert the job is scheduled
        $this->assertCount(1, $events, 'Sr operation schedules are not scheduled.');


        $event = $events->first();

        $this->assertInstanceOf(CallbackEvent::class, $event);

        // Assert the cron expression (every minute)
        $this->assertEquals('* * * * *', $event->expression);

        // (Optional) Assert that the scheduled job passes any time/day filters
        $this->assertTrue($event->filtersPass(app()));
    }

    public function test_sr_operation_schedules_dispatch_job_correctly()
    {
        // Mock the Event facade
        Event::fake([
            RunSrOperationEvent::class
        ]);

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

        list(
            $provider,
            $category,
            $s
        ) = $this->sharedPreparation($responseData, count($responseData));

        $provider->srs->each(function (Sr $sr) {

            $srSchedule = $sr->srSchedule()->create([
                'every_minute' => true,
            ]);
        });

        // Get the scheduler instance from the application container
        $schedule = app(Schedule::class);

        app(ProviderScheduleService::class)
            ->setSchedule($schedule)
            ->run();

        // Get all scheduled events and filter for your job
        $events = collect($schedule->events())->filter(function ($event) {

            // The job class name is often stored in the event's description
            return $event->description === 'Sr operation schedules';
        });

        // Assert the job is scheduled
        $this->assertCount(1, $events, 'Sr operation schedules are not scheduled.');


        $scheduledEvent = $events->first();

        $this->assertInstanceOf(CallbackEvent::class, $scheduledEvent);

        // Assert the cron expression (every minute)
        $this->assertEquals('* * * * *', $scheduledEvent->expression);

        // (Optional) Assert that the scheduled job passes any time/day filters
        $this->assertTrue($scheduledEvent->filtersPass(app()));

        // Use the updated helper to get the callback
        $closure = $this->getScheduledEventCallback($scheduledEvent);

        // Execute the closure to trigger the event
        $closure();

        // Assert the event was dispatched
        Event::assertDispatched(RunSrOperationEvent::class, function ($event) {
            if (!$event->userId) {
                return false;
            }
            $user = User::find($event->userId);
            if (!$user) {
                return false;
            }
            $configUser = config('services.scheduler.schedule_user_email');
            if (!$configUser) {
                return false;
            }
            return $user->email === $configUser;
        });
    }

    protected function getScheduledEventCallback($event)
    {
        $reflection = new \ReflectionClass($event);
        $callbackProperty = $reflection->getProperty('callback');
        return $callbackProperty->getValue($event);
    }

    public function sharedPreparation(?array $responseData = [], ?int $entityCount = 0)
    {

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

        $this->operationsDbHelpers->dataInit(
            $provider,
            $srResponseKeys,
            $s,
            $category,
            $properties,
            $srConfigs,
            $responseData,
            $entityCount
        );
        return [
            $provider,
            $category,
            $s,
        ];
    }
}
