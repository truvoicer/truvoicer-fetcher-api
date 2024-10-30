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
        switch ($importType) {
            case self::IMPORT_TYPES["CATEGORIES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Category::class);
                return $this->categoryImporterService->getImportMappings($deserializeXmlContent);
            case self::IMPORT_TYPES["PROVIDERS"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Provider::class);
                return $this->providerImporterService->getImportMappings($deserializeXmlContent);
            case self::IMPORT_TYPES["SERVICES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, S::class);
                return $this->apiServiceImporterService->getImportMappings($deserializeXmlContent);
            case self::IMPORT_TYPES["PROPERTIES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Property::class);
                return $this->propertyImporterService->getImportMappings($deserializeXmlContent);
            default:
                throw new BadRequestHttpException(
                    sprintf("Import mappings  type error.")
                );
        }
    }

    public function runImportForType($importType, $fileContents, array $mappings = [])
    {
        switch ($importType) {
            case self::IMPORT_TYPES["CATEGORIES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Category::class);
                return $this->categoryImporterService->import($deserializeXmlContent, $mappings);
            case self::IMPORT_TYPES["PROVIDERS"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Provider::class);
                return $this->providerImporterService->import($deserializeXmlContent, $mappings);
            case self::IMPORT_TYPES["SERVICES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, S::class);
                return $this->apiServiceImporterService->import($deserializeXmlContent, $mappings);
            case self::IMPORT_TYPES["PROPERTIES"]:
                $deserializeXmlContent = $this->serializerService->xmlArrayToEntities($fileContents, Property::class);
                return $this->propertyImporterService->import($deserializeXmlContent, $mappings);
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error.")
                );
        }
    }

    public function validateType($importType, array $data)
    {
        switch ($importType) {
            case self::IMPORT_TYPES["CATEGORIES"]:
                return $this->categoryImporterService->validateImportData($data);
            case self::IMPORT_TYPES["PROVIDERS"]:
                return $this->providerImporterService->validateImportData($data);
            case self::IMPORT_TYPES["SERVICES"]:
                return $this->apiServiceImporterService->validateImportData($data);
            case self::IMPORT_TYPES["PROPERTIES"]:
                return $this->propertyImporterService->validateImportData($data);
            default:
                throw new BadRequestHttpException(
                    sprintf("Import type error.")
                );
        }
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
