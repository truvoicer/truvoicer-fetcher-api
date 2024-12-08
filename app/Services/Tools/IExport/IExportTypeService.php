<?php

namespace App\Services\Tools\IExport;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
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
                $instance = App::make(CategoryImporterService::class);
                break;
            case ImportType::PROVIDER->value:
                $instance = App::make(ProviderImporterService::class);
                break;
            case ImportType::SERVICE->value:
                $instance = App::make(SImporterService::class);
                break;
            case ImportType::PROPERTY->value:
                $instance = App::make(PropertyImporterService::class);
                break;
            case ImportType::SR->value:
                $instance = App::make(SrImporterService::class);
                break;
            case ImportType::SR_RATE_LIMIT->value:
                $instance = App::make(SrRateLimitImporterService::class);
                break;
            case ImportType::SR_SCHEDULE->value:
                $instance = App::make(SrScheduleImporterService::class);
                break;
            case ImportType::SR_RESPONSE_KEY->value:
                $instance = App::make(SrResponseKeysImporterService::class);
                break;
            case ImportType::SR_PARAMETER->value:
                $instance = App::make(SrParameterImporterService::class);
                break;
            case ImportType::SR_CONFIG->value:
                $instance = App::make(SrConfigImporterService::class);
                break;
            case ImportType::PROVIDER_RATE_LIMIT->value:
                $instance = App::make(ProviderRateLimitImporterService::class);
                break;
            case ImportType::PROVIDER_PROPERTY->value:
                $instance = App::make(ProviderPropertiesImporterService::class);
                break;
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error. %s", $importType)
                );
        }
        $instance->setUser($this->getUser());
        return $instance;
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

    private function findContentByType(ImportType $importType, array $contents): array {
        return array_values(
            array_filter($contents, function ($content) use ($importType) {
                return $content['type'] === $importType->value;
            })
        );
    }

//    private function findContent(ImportType $importType, array $contents): array {
//        foreach ($contents as $content) {
//            $sra = $this->findSr($content['data'], ['srs', 'sr'], ['id' => 67]);
//            switch ($importType) {
//                case ImportType::SR:
//                    dd($sra);
////                case ImportType::SR_RESPONSE_KEY:
////                case ImportType::SR_RATE_LIMIT:
////                case ImportType::SR_CONFIG:
////                case ImportType::SR_PARAMETER:
////                case ImportType::SR_SCHEDULE:
//
//            }
//        }
//
//    }

    public function runImportForType(array $contents, array $mappings)
    {
        $instance = $this->getInstance(ImportType::PROVIDER->value);
        foreach (ImportType::cases() as $importType) {

            switch ($importType) {
//                case ImportType::SR:
//                case ImportType::SR_RESPONSE_KEY:
//                case ImportType::SR_RATE_LIMIT:
                case ImportType::SR_CONFIG:
//                case ImportType::SR_PARAMETER:
//                case ImportType::SR_SCHEDULE:
                $providerMappings = $this->getMappingsByType($importType->value, $mappings);
                $providerContent = $this->findContentByType(ImportType::PROVIDER, $contents);
                    foreach ($providerContent as $content) {
                        $sra = $instance->findSr($importType, $content['data'], ['id' => 223]);
                        dd($sra);
                    }
                dd($providerMappings, $importType->value);
                return $this->findMappings($mappings, $importType);

            }
        }
        return [];
        $response = [];
        foreach ($contents as $item) {
            $getMappingsByType = $this->getMappingsByType($item["type"], $mappings);
            $filteredMappings = array_filter($getMappingsByType, function ($mapping) {
                return !empty($mapping['mapping']['source']);
            });
            $data = $item['data'];
            $import = array_map(function (array $map) use($data) {
                dd('ss');
                return $this->destInterface($map, $data);
            }, array_values($filteredMappings));
            foreach ($import as $importItem) {
                $response[] = $importItem;
            }
        }
        return $response;
    }

    protected function destInterface(array $map, array $data): array
    {
        if (empty($map['mapping']['dest']) && empty($map['mapping']['source'])) {
            return [
                'success' => false,
                'error' => 'No source or destination found.',
            ];
        }
        if ((!empty($map['mapping']['dest']) && $map['mapping']['dest'] === 'root') || (empty($map['mapping']['dest']) && !empty($map['mapping']['source']))) {
            $importType = $map['mapping']['source'];
        }   else {
            $importType = $map['mapping']['dest'];
        }
        return $this->getInstance($importType)->importMapFactory($map, $data);
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
