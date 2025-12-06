<?php

namespace Feature\Frontend;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Category;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrParameter;
use App\Models\SrResponseKey;
use App\Models\User;
use Database\Seeders\PropertySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Laravel\Sanctum\Sanctum;
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

    /**
     * A basic test example.
     */
    public function test_list_search_operation(): void
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
                ProviderProperty::factory()
            )
            ->has(
                Sr::factory()
                    ->has(
                        SrConfig::factory()
                    )
                    ->has(
                        SrResponseKey::factory()
                    )
                    ->has(
                        SrParameter::factory()
                    )
            )->create();
            dd($provider);
        $postData = [
            "page_id" => 1,
            "api_fetch_type" => "database",
            "date_key" => "created_at",
            "page_number" => 1,
            "page_size" => 10,
            "service" => "recruitment",
            "sort_by" => "created_at",
            "sort_order" => "desc",
        ];
        $this->post(
            route('front.operation.search', ['type' => 'list']),
            $postData
        )
            ->assertStatus(200);
    }

    // public function test_api_can_import_mapped_data() {

    // }
}
