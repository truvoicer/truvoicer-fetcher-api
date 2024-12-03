<?php

namespace ImportExport;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Tests\TestCase;

class ExportTest extends TestCase
{
    private User $user;
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
//        $this->seed([RoleSeeder::class, UserSeeder::class]);
        $this->user = User::first();
        dd(User::all()->count());
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
}
