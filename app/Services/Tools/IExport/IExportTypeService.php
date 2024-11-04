<?php

namespace App\Services\Tools\IExport;

use App\Repositories\SrRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\Importer\Entities\SImporterService;
use App\Services\Tools\Importer\Entities\CategoryImporterService;
use App\Services\Tools\Importer\Entities\PropertyImporterService;
use App\Services\Tools\Importer\Entities\ProviderImporterService;
use App\Services\Tools\Importer\Entities\SrConfigImporterService;
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
        'SR_RATE_LIMIT' => SrRateLimitImporterService::class,
        'SR_SCHEDULE' => SrScheduleImporterService::class,
        'SR_RESPONSE_KEYS' => SrResponseKeysImporterService::class,
        'SR_PARAMETER' => SrParameterImporterService::class,
        'SR_CONFIG' => SrConfigImporterService::class,
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
            case self::IMPORT_TYPES["CATEGORIES"]:
                return $this->categoryImporterService;
            case self::IMPORT_TYPES["PROVIDERS"]:
                return $this->providerImporterService;
            case self::IMPORT_TYPES["SERVICES"]:
                return $this->apiServiceImporterService;
            case self::IMPORT_TYPES["PROPERTIES"]:
                return $this->propertyImporterService;
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error.")
                );
        }
    }

    public static function getImportMappingValue(string $importTypeName, string $destEntity, string $sourceEntity,
                                                 string $sourceItemName, array $mappings)
    {
        if (count($mappings) === 0) {
            return null;
        }
        if (isset($mappings[$importTypeName][$sourceEntity][$destEntity][$sourceItemName])) {
            $mappingValue = $mappings[$importTypeName][$sourceEntity][$destEntity][$sourceItemName];
            if ($mappingValue === null || $mappingValue === "") {
                return null;
            }
            return $mappingValue;
        }
        return null;
    }

    public function getImportDataMappings($importType, $fileContents)
    {
        return $this->getInstance($importType)->getImportMappings($fileContents);
    }

    public function runImportForType($importType, $fileContents, array $mappings = [])
    {
        return $this->getInstance($importType)->import($fileContents, $mappings);
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
