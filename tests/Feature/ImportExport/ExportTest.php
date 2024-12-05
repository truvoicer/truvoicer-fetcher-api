<?php

namespace ImportExport;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SResponseKey;
use App\Models\SrParameter;
use App\Models\SrRateLimit;
use App\Models\SrResponseKey;
use App\Models\SrSchedule;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Tools\IExport\ExportService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportTest extends TestCase
{
    private User $user;
    private string $token;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        $this->user = User::first();
//        $this->token = $this->user->createToken('superuser', [AuthService::ABILITY_SUPERUSER], Carbon::make('tomorrow'))->plainTextToken;
    }

    /**
     * A basic test example.
     */
    public function test_api_returns_export_list(): void
    {
        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->get(route('backend.tools.export.list'))
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
        Sanctum::actingAs(
            $this->user,
            ['*']
        );
        Storage::fake('downloads', [
            'driver' => 'local',
            'root' => storage_path('app/public/downloads'),
            'url' => env('APP_URL').'/storage/downloads',
            'file_download_url' => env('APP_URL').'/download/file',
            'visibility' => 'public',
            'throw' => false,
        ]);
        $category = Category::factory(2)->create();
        $property = Property::factory(2)->create();
        $services = S::factory(2)
            ->has(
                SResponseKey::factory(2)
            )
            ->create();
        $service = $services->first();

        $provider = Provider::factory(2)
            ->has(
                Sr::factory(2)
                ->has(
                    SrConfig::factory()->withProperty($property->first())
                )
                ->has(
                    SrParameter::factory(2)
                )
                ->has(
                    SrSchedule::factory()
                )
                ->has(
                    SrRateLimit::factory()
                )
                ->hasS($service)
            )
            ->create();
        $provider->each(function ($provider) use ($services) {
            $provider->srs->each(function ($sr) use ($services) {
                $service = $services->first();
                $sr->s()->associate($service)->save();
                SrResponseKey::factory()->create([
                    'sr_id' => $sr->id,
                    's_response_key_id' => $service->sResponseKey->first()->id,
                ]);
            });
        });
        $data = [
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
                    'export_data' => $services->toArray(),
                ],
                [
                    'export_type' => 'provider',
                    'export_data' => $provider->toArray(),
                ]
            ]
        ];

        $responseData = ExportService::getInstance()->setUser($this->user)->getExportDataArray($data);

        $this->assertIsArray($responseData);
        foreach ($responseData as $key => $value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey('type', $value);
            $this->assertArrayHasKey('data', $value);
            $this->assertIsArray($value['data']);
        }

        $this->postJson(
            route('backend.tools.export'),
            $data
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
        $file =File::first();
        $fileDownload = FileDownload::first();

        Storage::disk('downloads')->assertExists($file->rel_path);
        $this->actingAs($this->user)->get(
            route('download.file', ['file_download' => $fileDownload->download_key]),
        )
            ->assertStatus(200)->assertStreamedContent(json_encode($responseData));
    }
    public function test_api_import_can_parse_a_json__file(): void
    {
        $category = Category::factory(2)->create();
        $property = Property::factory(2)->create();
        $services = S::factory(2)
            ->has(
                SResponseKey::factory(2)
            )
            ->create();
        $service = $services->first();

        $provider = Provider::factory(2)
            ->has(
                Sr::factory(2)
                ->has(
                    SrConfig::factory()->withProperty($property->first())
                )
                ->has(
                    SrParameter::factory(2)
                )
                ->has(
                    SrSchedule::factory()
                )
                ->has(
                    SrRateLimit::factory()
                )
                ->hasS($service)
            )
            ->create();
        $provider->each(function ($provider) use ($services) {
            $provider->srs->each(function ($sr) use ($services) {
                $service = $services->first();
                $sr->s()->associate($service)->save();
                SrResponseKey::factory()->create([
                    'sr_id' => $sr->id,
                    's_response_key_id' => $service->sResponseKey->first()->id,
                ]);
            });
        });
        $data = [
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
                    'export_data' => $services->toArray(),
                ],
                [
                    'export_type' => 'provider',
                    'export_data' => $provider->toArray(),
                ]
            ]
        ];

        $this->postJson(
            route('backend.tools.export'),
            $data
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
        $file =File::first();
        $fileDownload = FileDownload::first();

        Storage::disk('downloads')->assertExists($file->rel_path);
        $this->actingAs($this->user)->get(
            route('download.file', ['file_download' => $fileDownload->download_key]),
        )
            ->assertStatus(200)->assertStreamedContent(json_encode($responseData));
    }
}
