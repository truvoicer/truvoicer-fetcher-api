<?php

namespace App\Services\Tools\IExport;

use App\Exceptions\ImportException;
use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Repositories\SrRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\Importer\Entities\SImporterService;
use App\Services\Tools\Importer\Entities\CategoryImporterService;
use App\Services\Tools\Importer\Entities\PropertyImporterService;
use App\Services\Tools\Importer\Entities\ProviderImporterService;
use App\Services\Tools\SerializerService;
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
    const EXPORT_TYPES = [
        "CATEGORIES" => "categories",
        "PROVIDERS" => "providers",
        "SERVICES" => "services",
        "PROPERTIES" => "properties"
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
        return array_filter($data, function ($item) {
            $instance = $this->getInstance($item["type"]);
            return $instance->filterImportData($item['data']);
        }, ARRAY_FILTER_USE_BOTH);
    }


    public function getExportTypeData($exportType, $data)
    {
        $this->accessControlService->setUser($this->getUser());
        return array_map(function ($item) use ($exportType) {
            switch ($exportType) {
                case self::EXPORT_TYPES["CATEGORIES"]:
                    $category = $this->categoryImporterService->getCategoryService()->getCategoryById($item["id"]);
                    if ($this->accessControlService->inAdminGroup()) {
                        return $category;
                    }

                    $isPermitted = $this->accessControlService->checkPermissionsForEntity(
                        $category,
                        [
                            PermissionService::PERMISSION_ADMIN,
                            PermissionService::PERMISSION_READ,
                        ],
                        false
                    );
                    return $isPermitted ? $category : false;
                case self::EXPORT_TYPES["PROVIDERS"]:
                    $srs = (!empty($item["srs"]) && is_array($item["srs"])) ?
                        $item["srs"] : [];
                    $this->providerImporterService->getProviderService()->getProviderRepository()->setWith([
                        'sr' => function ($query) use ($srs) {
                            $query->whereIn('id', array_column($srs, 'id'));

                            $childSrs = [];
                            foreach ($srs as $sr) {
                                if (is_array($sr['child_srs'])) {
                                    $childSrs = array_merge($childSrs, $sr['child_srs']);
                                }
                            }
                            $query = $this->srRepository->buildNestedSrQuery(
                                $query,
                                $childSrs,
                                [
                                    'srConfig' => function ($query) {
                                        $query->with('property');
                                    },
                                    'srParameter',
                                    'srSchedule',
                                    'srRateLimit',
                                    'srResponseKeys' => function ($query) {
                                        $query->with('srResponseKeySrs');
                                    },
                                    's',
                                    'category'
                                ]
                            );
                        },
                        'categories'
                    ]);
                    $provider = $this->providerImporterService->getProviderService()->getProviderRepository()->findById(
                        $item["id"],
                    );

                    if ($this->accessControlService->inAdminGroup()) {
                        return $provider->toArray();
                    }

                    $isPermitted = $this->accessControlService->checkPermissionsForEntity(
                        $provider,
                        [
                            PermissionService::PERMISSION_ADMIN,
                            PermissionService::PERMISSION_READ,
                        ],
                        false
                    );
                    return $isPermitted ? $provider->toArray() : false;
                case self::EXPORT_TYPES["SERVICES"]:
                    return $this->apiServiceImporterService->getApiService()->getServiceById($item["id"]);
                case self::EXPORT_TYPES["PROPERTIES"]:
                    return $this->propertyImporterService->getPropertyService()->getPropertyById($item["id"]);
                default:
                    throw new BadRequestHttpException(
                        sprintf("Export data validation error:  Type (%s): Error in position id (%d).", $exportType, $item["id"])
                    );
            }
        }, $data[self::REQUEST_KEYS["EXPORT_DATA"]]);
    }
}
