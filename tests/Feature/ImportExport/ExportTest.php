<?php

namespace ImportExport;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\Import\ImportConfig;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\User;
use App\Services\Tools\IExport\ExportService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Resources\Data\ImportExport\DefaultData;
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
            'url' => env('APP_URL') . '/storage/downloads',
            'file_download_url' => env('APP_URL') . '/download/file',
            'visibility' => 'public',
            'throw' => false,
        ]);
        list($categories, $properties, $providers, $services) = DefaultData::fullData();

        $data = DefaultData::exportRequestData($categories, $properties, $providers, $services);

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
        $file = File::first();
        $fileDownload = FileDownload::first();

        Storage::disk('downloads')->assertExists($file->rel_path);
        $this->actingAs($this->user)->get(
            route('download.file', ['file_download' => $fileDownload->download_key]),
        )
            ->assertStatus(200)->assertStreamedContent(json_encode($responseData));
    }


    public function test_api_import_can_parse_a_json_file(): void
    {
        Sanctum::actingAs(
            $this->user,
            ['*']
        );
        Storage::fake('downloads', [
            'driver' => 'local',
            'root' => storage_path('app/public/downloads'),
            'url' => env('APP_URL') . '/storage/downloads',
            'file_download_url' => env('APP_URL') . '/download/file',
            'visibility' => 'public',
            'throw' => false,
        ]);
        list($categories, $properties, $providers, $services) = DefaultData::fullData();

        $data = DefaultData::exportRequestData($categories, $properties, $providers, $services);

        $responseData = ExportService::getInstance()->setUser($this->user)->getExportDataArray($data);

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

        $file = UploadedFile::fake()->createWithContent('export.json', json_encode($responseData));
        $response = $this->withHeaders(
            [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]
        )
            ->postJson(
                route('backend.tools.import.parse'),
                [
                    'upload_file' => $file,
                ]
            )
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'file',
                    'config',
                    'data',
                ],
                'errors',
            ]);
        $content = $response->getOriginalContent();
        $fileModel = File::orderBy('id', 'desc')->first();

        $this->assertIsArray($content['data']);
        $this->assertArrayHasKey('file', $content['data']);
        $this->assertArrayHasKey('config', $content['data']);
        $this->assertArrayHasKey('data', $content['data']);
        $this->assertIsObject($content['data']['file']);
        $this->assertIsArray($content['data']['data']);

        $this->assertInstanceOf(File::class, $content['data']['file']);
        foreach ($fileModel->getFillable() as $key) {
            $this->assertEquals($content['data']['file']->{$key}, $fileModel->{$key});
        }

        $this->assertIsArray($content['data']['config']);
        self::assertEmpty(
            array_filter(
                $content['data']['config'],
                fn($key) => !!ImportConfig::tryFrom($key),
                ARRAY_FILTER_USE_KEY
            )
        );

        foreach ($content['data']['data'] as $key => $value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey('root', $value);
            self::assertIsBool($value['root']);
            $this->assertArrayHasKey('import_type', $value);
            self::assertIsString($value['import_type']);
            $this->assertArrayHasKey('label', $value);
            self::assertIsString($value['label']);
            $this->assertArrayHasKey('children', $value);
            $this->assertIsArray($value['children']);
        }
    }

    public function test_api_can_import_mapped_data() {

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
        Storage::fake('downloads', [
            'driver' => 'local',
            'root' => storage_path('app/public/downloads'),
            'url' => env('APP_URL') . '/storage/downloads',
            'file_download_url' => env('APP_URL') . '/download/file',
            'visibility' => 'public',
            'throw' => false,
        ]);
        list($categories, $properties, $providers, $services) = DefaultData::fullData();

        $data = DefaultData::exportRequestData($categories, $properties, $providers, $services);

        $responseData = ExportService::getInstance()->setUser($this->user)->getExportDataArray($data);

        $file = File::create([
            'name' => 'export.json',
            'filename' => 'export.json',
            'full_path' => '/var/www/html/storage/app/public/downloads/exports/export.json',
            'rel_path' => 'exports/export.json',
            'extension' => 'json',
            'type' => 'export',
            'size' => 1000,
            'file_system' => 'downloads',
            'mime_type' => 'application/json',
        ]);
        $file = UploadedFile::fake()->createWithContent('export.json', json_encode($responseData));

        $mappingsData = [];
        $response = $this->withHeaders(
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        )
            ->postJson(
                route('backend.tools.import.mappings'),
                [
                    'mappings' => $mappingsData,
                    'file_id' => $file->id,
                ]
            )
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'file',
                    'config',
                    'data',
                ],
                'errors',
            ]);
    }
}
