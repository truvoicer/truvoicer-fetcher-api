<?php

namespace Tests\Feature\Frontend;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use App\Enums\Property\PropertyType;
use App\Enums\Sr\SrType;
use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrParameter;
use App\Models\SrResponseKey;
use App\Models\User;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Data\DataConstants;
use Database\Seeders\PropertySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Frontend\OperationsControllerData\DeepSeekData;
use Tests\Feature\Frontend\OperationsControllerData\GeminiData;
use Tests\Feature\Frontend\OperationsControllerData\GrokData;
use Tests\Feature\Frontend\OperationsControllerData\OpenAiData;
use Tests\TestCase;

class OperationsControllerTest extends TestCase
{
    private User $superUser;


    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
        $this->superUser = User::first();
    }

    #[DataProvider('providerPropertyProvider')]
    public function test_list_search_operation(
        array $properties,
        array $srConfigs,
        array $srResponseKeys,
        array $requestResponse,
        array $responseData,
        array $afterResponseData
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
                    'default_sr' => true
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
                'Response key (items_array) value is empty.',
                $exception->getMessage()
            );
        }
        if (!empty($responseData)) {

            $response->assertJson([
                'results' => array_map(function (array $item) use($provider, $category) {
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

    public static function providerPropertyProvider(): array
    {
        return [
            ...DeepSeekData::data(),
            ...GrokData::data(),
            ...OpenAiData::data(),
            ...GeminiData::data(),
        ];
    }
}
