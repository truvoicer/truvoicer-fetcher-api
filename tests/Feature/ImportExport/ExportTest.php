<?php

namespace ImportExport;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Tests\TestCase;

class ExportTest extends TestCase
{
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        $this->user = User::first();
    }

    /**
     * A basic test example.
     */
    public function test_api_returns_export_list(): void
    {
        $this->actingAs($this->user)->getJson(route('backend.tools.export.list'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'show',
                        'id',
                        'name',
                        'label',
                        'children_keys',
                        'name_field',
                        'label_field',
                        'root_label_field',
                        'import_mappings',
                    ]
                ]
            ]);
    }

    public function test_api_exports_to_json_file(): void
    {
        $category = Category::factory(2)->create();
        $property = Property::factory(2)->create();
        $service = S::factory(2)->create();
        $provider = Provider::factory()->create();

        $this->actingAs($this->user)->postJson(
            route('backend.tools.export'),
            [
                'data' => [
                    [
                        'export_type' => 'category',
                        'export_data' => $category->toArray(),
                    ],
                    [
                        'export_type' => 'property',
                        'export_data' => $property->toArray(),
                    ],
                    [
                        'export_type' => 'service',
                        'export_data' => $service->toArray(),
                    ],
                ]
            ]
        )
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'file_url',
                ],
                'errors',
            ]);
    }
}
