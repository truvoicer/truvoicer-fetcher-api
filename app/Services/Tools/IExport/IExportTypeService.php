<?php

namespace App\Services\Tools\IExport;

use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\Importer\Entities\ApiServiceImporterService;
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
        "PROPERTIES" => "properties"
    ];
    const EXPORT_TYPES = [
        "CATEGORIES" => "categories",
        "PROVIDERS" => "providers",
        "SERVICES" => "services",
        "PROPERTIES" => "properties"
    ];

    protected CategoryImporterService $categoryImporterService;
    protected ProviderImporterService $providerImporterService;
    protected ApiServiceImporterService $apiServiceImporterService;
    protected PropertyImporterService $propertyImporterService;
    protected SerializerService $serializerService;
    protected AccessControlService $accessControlService;

    public function __construct(
        CategoryImporterService   $categoryMappingsService,
        ProviderImporterService   $providerMappingsService,
        ApiServiceImporterService $apiServiceMappingsService,
        SerializerService         $serializerService,
        PropertyImporterService   $propertyImporterService,
        AccessControlService      $accessControlService
    )
    {
        parent::__construct();
        $this->categoryImporterService = $categoryMappingsService;
        $this->providerImporterService = $providerMappingsService;
        $this->apiServiceImporterService = $apiServiceMappingsService;
        $this->propertyImporterService = $propertyImporterService;
        $this->serializerService = $serializerService;
        $this->accessControlService = $accessControlService;
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

    public function validateType($importType, array $data)
    {
        return $this->getInstance($importType)->validateImportData($data);
    }



    public function getExportTypeData($exportType, $data)
    {
        $this->accessControlService->setUser($this->getUser());
        return array_map(function ($item) use ($exportType) {
            switch ($exportType) {
                case self::EXPORT_TYPES["CATEGORIES"]:
                    $category = $this->categoryImporterService->getCategoryById($item["id"]);
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
                    $provider = $this->providerImporterService->getProviderRepository()->findProviderById(
                        $item["id"],
                        (!empty($item["srs"]) && is_array($item["srs"])) ?
                            $item["srs"] : []
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
                    return $this->apiServiceImporterService->getServiceById($item["id"]);
                case self::EXPORT_TYPES["PROPERTIES"]:
                    return $this->propertyImporterService->getPropertyById($item["id"]);
                default:
                    throw new BadRequestHttpException(
                        sprintf("Export data validation error:  Type (%s): Error in position id (%d).", $exportType, $item["id"])
                    );
            }
        }, $data[self::REQUEST_KEYS["EXPORT_DATA"]]);
    }
}
