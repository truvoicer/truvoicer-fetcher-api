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
use App\Services\Tools\Importer\Entities\SResponseKeysImporterService;
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

    private function getInstance(ImportType $importType): ImporterBase
    {
        switch ($importType) {
            case ImportType::CATEGORY:
                $instance = App::make(CategoryImporterService::class);
                break;
            case ImportType::PROVIDER:
                $instance = App::make(ProviderImporterService::class);
                break;
            case ImportType::SERVICE:
                $instance = App::make(SImporterService::class);
                break;
            case ImportType::PROPERTY:
                $instance = App::make(PropertyImporterService::class);
                break;
            case ImportType::SR:
                $instance = App::make(SrImporterService::class);
                break;
            case ImportType::SR_RATE_LIMIT:
                $instance = App::make(SrRateLimitImporterService::class);
                break;
            case ImportType::SR_SCHEDULE:
                $instance = App::make(SrScheduleImporterService::class);
                break;
            case ImportType::SR_RESPONSE_KEY:
                $instance = App::make(SrResponseKeysImporterService::class);
                break;
            case ImportType::S_RESPONSE_KEY:
                $instance = App::make(SResponseKeysImporterService::class);
                break;
            case ImportType::SR_PARAMETER:
                $instance = App::make(SrParameterImporterService::class);
                break;
            case ImportType::SR_CONFIG:
                $instance = App::make(SrConfigImporterService::class);
                break;
            case ImportType::PROVIDER_RATE_LIMIT:
                $instance = App::make(ProviderRateLimitImporterService::class);
                break;
            case ImportType::PROVIDER_PROPERTY:
                $instance = App::make(ProviderPropertiesImporterService::class);
                break;
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error. %s", $importType->value)
                );
        }
        $instance->setUser($this->getUser());
        return $instance;
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

    private function findContentByType(ImportType $importType, array $contents): array
    {
        switch ($importType) {
            case ImportType::SR:
            case ImportType::SR_RESPONSE_KEY:
            case ImportType::SR_RATE_LIMIT:
            case ImportType::SR_CONFIG:
            case ImportType::SR_PARAMETER:
            case ImportType::SR_SCHEDULE:
                $filteredContent = array_values(
                    array_filter($contents, function ($content) use ($importType) {
                        return $content['type'] === ImportType::PROVIDER->value;
                    })
                );
                $data = [];
                foreach ($filteredContent as $content) {
                    if (empty($content['data']) || !is_array($content['data'])) {
                        continue;
                    }
                    foreach ($content['data'] as $item) {
                        if (!empty($item['srs']) && is_array($item['srs'])) {
                            $data = array_merge($data, $item['srs']);
                        } elseif (!empty($item['child_srs']) && is_array($item['child_srs'])) {
                            $data = array_merge($data, $item['child_srs']);
                        }
                    }
                }
                $contents = [
                    [
                        'type' => $importType->value,
                        'data' => $data
                    ]
                ];
                break;
            default:
                $contents = array_values(
                    array_filter($contents, function ($content) use ($importType) {
                        return $content['type'] === $importType->value;
                    })
                );
        }
        return $contents;
    }


    public function runImportForType(array $contents, array $mappings)
    {
        $response = [];
        foreach (ImportType::cases() as $importType) {
            $instance = null;
            $instance = $this->getInstance($importType);
            $filterMappings = $this->getMappingsByType($importType->value, $mappings);
            $filterContent = $this->findContentByType($importType, $contents);
            foreach ($filterContent as $content) {
                $import = array_map(function (array $map) use ($instance, $importType, $content) {
                    if (!empty($map['root']) && !empty($map['children']) && is_array($map['children']) && count($map['children'])) {
                        $conditions = array_map(function ($child) {
                            return ['id' => $child['id']];
                        }, $map['children']);
                        $operation = 'OR';
                    } else {
                        $conditions = [['id' => $map['id']]];
                        $operation = 'AND';
                    }
                    $data = $instance->deepFind($importType, $content['data'], $conditions, $operation);
                    if (empty($data)) {
                        return [
                            'success' => false,
                            'error' => 'No data found.',
                        ];
                    }
                    return $this->destInterface($map, $data);
                }, array_values($filterMappings));
                foreach ($import as $importItem) {
                    $response[] = $importItem;
                }
            }
        }
        return $response;
    }

    protected function destInterface(array $map, array $data): array
    {
        if (empty($map['mapping']['source'])) {
            return [
                'success' => false,
                'error' => 'No source or destination found.',
            ];
        }
        if ((!empty($map['mapping']['dest']) && $map['mapping']['dest'] === 'root')) {
            $importType = $map['mapping']['source'];
        } else {
            $importType = $map['mapping']['source'];
        }

        $dest = (!empty($map['dest'])) ? $map['dest'] :null;
        return $this->getInstance(ImportType::from($importType))->importMapFactory($map, $data, $dest);
    }

    public function validateType(ImportType $importType, array $data): void
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
            $this->validateType(ImportType::from($item["type"]), $item['data']);
        }
    }

    public function filterImportData(array $data): array
    {
        return array_map(function ($item) {
            $instance = $this->getInstance(ImportType::from($item["type"]));
            return $instance->filterImportData($item['data']);
        }, $data);
    }


    public function getExportTypeData($exportType, $data): array
    {
        return array_map(function ($item) use ($exportType) {
            $instance = $this->getInstance(ImportType::from($exportType));
            $instance->setUser($this->getUser());
            $instance->getAccessControlService()->setUser($this->getUser());
            return $instance->getExportTypeData($item);
        }, $data[self::REQUEST_KEYS["EXPORT_DATA"]]);
    }

    private static function filterMatch(?array $filter, array $config): bool
    {
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
