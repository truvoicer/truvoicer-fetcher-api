<?php

namespace App\Services\Tools\IExport;

use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Repositories\SrRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\Importer\Entities\ImporterBase;
use App\Services\Tools\Importer\Entities\ProviderPropertiesImporterService;
use App\Services\Tools\Importer\Entities\ProviderRateLimitImporterService;
use App\Services\Tools\Importer\Entities\SImporterService;
use App\Services\Tools\Importer\Entities\CategoryImporterService;
use App\Services\Tools\Importer\Entities\PropertyImporterService;
use App\Services\Tools\Importer\Entities\ProviderImporterService;
use App\Services\Tools\Importer\Entities\SrConfigImporterService;
use App\Services\Tools\Importer\Entities\SrImporterService;
use App\Services\Tools\Importer\Entities\SrParameterImporterService;
use App\Services\Tools\Importer\Entities\SrRateLimitImporterService;
use App\Services\Tools\Importer\Entities\SrResponseKeysImporterService;
use App\Services\Tools\Importer\Entities\SrScheduleImporterService;
use App\Services\Tools\SerializerService;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IExportTypeService extends BaseService
{
    const REQUEST_KEYS = [
        "EXPORT_TYPE" => "export_type",
        "EXPORT_DATA" => "export_data",
    ];
    const IMPORT_TYPES = [
        "CATEGORIES" => "categories",
        "PROVIDERS" => "providers",
        "SERVICES" => "services",
        "PROPERTIES" => "properties",
//        'SR_RATE_LIMIT' => 'sr_rate_limit',
//        'SR_SCHEDULE' => 'sr_schedule',
//        'SR_RESPONSE_KEYS' => 'sr_response_keys',
//        'SR_PARAMETER' => 'sr_parameter',
//        'SR_CONFIG' => 'sr_config',
    ];
    public const IMPORTERS = [
        "CATEGORIES" => CategoryImporterService::class,
        "PROVIDERS" => ProviderImporterService::class,
        "SERVICES" => SImporterService::class,
        "PROPERTIES" => PropertyImporterService::class,
        'SRS' => SrImporterService::class,
        'SR_RATE_LIMIT' => SrRateLimitImporterService::class,
        'SR_SCHEDULE' => SrScheduleImporterService::class,
        'SR_RESPONSE_KEYS' => SrResponseKeysImporterService::class,
        'SR_PARAMETER' => SrParameterImporterService::class,
        'SR_CONFIG' => SrConfigImporterService::class,
        'PROVIDER_RATE_LIMIT' => ProviderRateLimitImporterService::class,
        'PROVIDER_PROPERTIES' => ProviderPropertiesImporterService::class,
    ];
    protected CategoryImporterService $categoryImporterService;
    protected ProviderImporterService $providerImporterService;
    protected SImporterService $apiServiceImporterService;
    protected PropertyImporterService $propertyImporterService;
    protected SerializerService $serializerService;
    protected AccessControlService $accessControlService;
    protected SrRepository $srRepository;

    public function __construct(
        CategoryImporterService $categoryMappingsService,
        ProviderImporterService $providerMappingsService,
        SImporterService        $apiServiceMappingsService,
        SerializerService       $serializerService,
        PropertyImporterService $propertyImporterService,
        AccessControlService    $accessControlService
    )
    {
        parent::__construct();
        $this->categoryImporterService = $categoryMappingsService;
        $this->providerImporterService = $providerMappingsService;
        $this->apiServiceImporterService = $apiServiceMappingsService;
        $this->propertyImporterService = $propertyImporterService;
        $this->serializerService = $serializerService;
        $this->accessControlService = $accessControlService;
        $this->srRepository = new SrRepository();
    }

    private function getInstance(string $importType)
    {
        switch ($importType) {
            case ImportType::CATEGORY->value:
                $this->categoryImporterService->setUser($this->getUser());
                return $this->categoryImporterService;
            case ImportType::PROVIDER->value:
                $this->providerImporterService->setUser($this->getUser());
                return $this->providerImporterService;
            case ImportType::SERVICE->value:
                $this->apiServiceImporterService->setUser($this->getUser());
                return $this->apiServiceImporterService;
            case ImportType::PROPERTY->value:
                $this->propertyImporterService->setUser($this->getUser());
                return $this->propertyImporterService;
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error.")
                );
        }
    }

    public function getImportDataMappings($importType, $fileContents)
    {
        return $this->getInstance($importType)->getImportMappings($fileContents);
    }

    private function getMappingsByType(string $type, array $mappings): array
    {
        return array_filter($mappings, function ($mapping) use ($type) {
            if (empty($mapping['mapping']['source'])) {
                return false;
            }
            return $mapping['mapping']['source'] === $type;
        });
    }

    public function runImportForType(array $contents, array $mappings)
    {
//        [ // app/Services/Tools/IExport/IExportTypeService.php:134
//            0 => "category"
//          1 => "provider"
//          2 => "service"
//          3 => "property"
//        ]
        dd($contents);
        return array_map(function ($item) use($mappings) {
            $getMappingsByType = $this->getMappingsByType($item["type"], $mappings);
            $filteredMappings = array_filter($getMappingsByType, function ($mapping) {
                return !empty($mapping['mapping']['source']);
            });
            return $this->import($item['data'], $filteredMappings);
        }, $contents);
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (array $map) use($data) {
            return $this->destInterface($map, $data);
        }, $mappings);
    }

    protected function destInterface(array $map, array $data): array
    {
        if (empty($map['mapping']['dest']) && empty($map['mapping']['source'])) {
            return [
                'success' => false,
                'error' => 'No source or destination found.',
            ];
        }
        if (empty($map['mapping']['dest']) && !empty($map['mapping']['source'])) {
            $importType = $map['mapping']['source'];
        }   else {
            $importType = $map['mapping']['dest'];
        }
        $instance = $this->getInstance($importType);
        return $this->importMapInterface($instance, $map['mapping']['name'], $map, $data);
    }

    private function importMapInterface(ImporterBase $instance, string $mapName, array $map, array $data): array
    {
        return match ($mapName) {
            ImportMappingType::SELF_NO_CHILDREN->value => $instance->importSelfNoChildren($map, $data),
            ImportMappingType::SELF_WITH_CHILDREN->value => $instance->importSelfWithChildren($map, $data),
            default => [
                'success' => false,
                'data' => $map['data'],
            ],
        };
    }

    public function validateType($importType, array $data): void
    {
        $instance = $this->getInstance($importType);
        $instance->validateImportData($data);
        if ($instance->hasErrors()) {
            $this->setErrors(array_merge(
                $this->getErrors(),
                $instance->getErrors()
            ));
        }
    }

    public function validateTypeBatch(array $data): void
    {
        foreach ($data as $item) {
            $this->validateType($item["type"], $item['data']);
        }
    }

    public function filterImportData(array $data): array
    {
        return array_map(function ($item) {
            $instance = $this->getInstance($item["type"]);
            return $instance->filterImportData($item['data']);
        }, $data);
    }


    public function getExportTypeData($exportType, $data): array
    {
        return array_map(function ($item) use ($exportType) {
            $instance = $this->getInstance($exportType);
            $instance->setUser($this->getUser());
            $instance->getAccessControlService()->setUser($this->getUser());
            return $instance->getExportTypeData($item);
        }, $data[self::REQUEST_KEYS["EXPORT_DATA"]]);
    }

    private static function filterMatch(?array $filter, array $config): bool{
        if (count($filter) > 0) {
            if (
                array_filter($config, function ($value, $key) use ($filter) {
                    return in_array($key, array_keys($filter)) && $filter[$key] === $value;
                }, ARRAY_FILTER_USE_BOTH) === []) {
                return false;
            }
        }
        return true;
    }

    public static function getImporterConfigs(array $importerClassnames, ?array $filter = []): array
    {
        $configs = [];
        foreach ($importerClassnames as $importerClassname) {
            $instance = App::make($importerClassname);
            $config = $instance->getConfig();
            if (!self::filterMatch($filter, $config)) {
                continue;
            }
            $configs[] = $config;
        }
        return $configs;
    }

    public function getImporterExportData(array $importerClassnames, ?array $filter = []): array
    {
        $configs = [];
        foreach ($importerClassnames as $importerClassname) {
            $instance = App::make($importerClassname);
            $instance->setUser($this->getUser());
            $instance->getAccessControlService()->setUser($this->getUser());
            $config = $instance->getConfig();
            if (!self::filterMatch($filter, $config)) {
                continue;
            }
            $config['data'] = $instance->getExportData();
            $configs[] = $config;
        }
        return $configs;
    }

    public static function getExportTypes(): array
    {
        return array_map(function ($config) {
            return $config['name'];
        }, self::getImporterConfigs(self::IMPORTERS, ["show" => true]));
    }
}
