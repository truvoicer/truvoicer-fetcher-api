<?php

namespace App\Services\Tools\IExport;

use App\Models\User;
use App\Services\ApiServices\ApiService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Property\PropertyService;
use App\Services\Provider\ProviderService;
use App\Services\ServiceFactory;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\SerializerService;
use App\Traits\User\UserTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ExportService
{
    use UserTrait;

    private SerializerService $serializerService;
    private DownloadsFileSystemService $downloadsFileSystem;
    private IExportTypeService $iExportTypeService;
    private ServiceFactory $serviceFactory;
    protected AccessControlService $accessControlService;

    public function __construct(
        SerializerService          $serializerService,
        DownloadsFileSystemService $downloadsFileSystemService,
        IExportTypeService         $iExportTypeService,
        ServiceFactory             $serviceFactory,
        AccessControlService       $accessControlService,
        private CategoryService    $categoryService,
        private PropertyService    $propertyService,
        private ProviderService    $providerService,
        private ApiService         $apiService
    )
    {
        $this->serializerService = $serializerService;
        $this->downloadsFileSystem = $downloadsFileSystemService;
        $this->iExportTypeService = $iExportTypeService;
        $this->serviceFactory = $serviceFactory;
        $this->accessControlService = $accessControlService;
    }

    public static function getExportEntityFields()
    {
        $exportEntityData = [];
        foreach (IExportTypeService::EXPORT_TYPES as $key => $type) {
            switch ($type) {
                case IExportTypeService::EXPORT_TYPES["CATEGORIES"]:
                    $exportEntityData[] = [
                        "show" => false,
                        "id" => "id",
                        "name" => "categories",
                        "label" => "Categories",
                        "nameField" => "name",
                        "labelField" => "label",
                    ];
                    break;
                case IExportTypeService::EXPORT_TYPES["PROVIDERS"]:
                    $exportEntityData[] = [
                        "show" => false,
                        "id" => "id",
                        "name" => "providers",
                        "label" => "Providers",
                        "nameField" => "name",
                        "labelField" => "label",
                    ];
                    break;
                case IExportTypeService::EXPORT_TYPES["SERVICES"]:
                    $exportEntityData[] = [
                        "show" => false,
                        "id" => "id",
                        "name" => "services",
                        "label" => "Services",
                        "nameField" => "name",
                        "labelField" => "label",
                    ];
                    break;
                case IExportTypeService::EXPORT_TYPES["PROPERTIES"]:
                    $exportEntityData[] = [
                        "show" => false,
                        "id" => "id",
                        "name" => "properties",
                        "label" => "Properties",
                        "nameField" => "property_name",
                        "labelField" => "label",
                    ];
                    break;
            }
        }
        return $exportEntityData;
    }

    public function getExportEntityListData(User $user)
    {
        $this->accessControlService->setUser($user);
        $exportEntityData = self::getExportEntityFields();
        foreach ($exportEntityData as $index => $type) {
            switch ($type['name']) {
                case IExportTypeService::EXPORT_TYPES["CATEGORIES"]:
                    $exportEntityData[$index]['data'] = $this->categoryService->findUserCategories(
                        $user,
                        false
                    )->toArray();
                    break;
                case IExportTypeService::EXPORT_TYPES["PROVIDERS"]:
                    $exportEntityData[$index]['data'] = $this->providerService->findProviders(
                        $user
                    )->toArray();
                    break;
                case IExportTypeService::EXPORT_TYPES["SERVICES"]:
                    $exportEntityData[$index]['data'] = $this->apiService->findUserServices(
                        $user,
                        false
                    )->toArray();
                    break;
                case IExportTypeService::EXPORT_TYPES["PROPERTIES"]:
                    $data = [];
                    if ($this->accessControlService->inAdminGroup()) {
                        $data = $this->propertyService->findPropertiesByParams(
                            $user,
                            false
                        )->toArray();
                    }
                    $exportEntityData[$index]['data'] = $data;
                    break;
            }
        }
        return $exportEntityData;
    }

    public function validateRequest($data)
    {
        if (!array_key_exists("data", $data)) {
            throw new BadRequestHttpException("Error: (data) key not in request.");
        }
        if (!is_array($data["data"])) {
            throw new BadRequestHttpException("Error: (data) is not an array.");
        }
    }

    public function storeXmlDataFromArray(array $xmlArray)
    {
        if (!count($xmlArray)) {
            return [
                "status" => "error",
                "message" => "Export Store error: No data to export."
            ];
        }
        $date = new \DateTime();
        $dateString = $date->format("YmdHis");
        $rootDir = "exports";
        $responseArray = [];
        $fileName = sprintf("export-%s.json", $dateString);

        $fileDirectory = sprintf("/%s/%s", $rootDir, $fileName);

        $storeData = $this->downloadsFileSystem->storeNewDownloadsFile(
            $fileDirectory,
            $fileName,
            json_encode($xmlArray)
        );

        if (!$storeData) {
            return [
                "status" => "error",
                "message" => "Export Store error: Unable to store data."
            ];
        }

        $getSavedData = $this->downloadsFileSystem->saveDownloadsFileToDatabase(
            $fileDirectory,
            $fileName,
            "export",
            "json"
        );
        if (!$getSavedData) {
            $responseArray["status"] = "error";
            $responseArray["message"] = sprintf(
                "Export save item error: Type (%s) unable to save item to database.",
                'json'
            );
        } else {
            $responseArray["status"] = "success";
            $responseArray["message"] = sprintf("Export file saved: Type (%s)", 'json');
            $responseArray["type"] = 'json';
            $responseArray["data"] = $getSavedData;
            $responseArray["file_url"] = $this->downloadsFileSystem->getFileDownloadUrl($getSavedData);
        }
        return $responseArray;
    }

    public function getExportDataArray(array $data)
    {
        $this->iExportTypeService->setUser($this->getUser());
        $this->validateRequest($data);
        return array_map(function ($exportItem) {
            $exportType = $this->getExportType($exportItem);
            $this->validateExportRequest($exportType, $exportItem);
            return [
                "type" => $exportType,
                "data" => $this->iExportTypeService->getExportTypeData($exportType, $exportItem)
            ];
        }, $data["data"]);
    }

    public function getExportType(array $data)
    {
        if (!array_key_exists(IExportTypeService::REQUEST_KEYS["EXPORT_TYPE"], $data)) {
            throw new BadRequestHttpException("Export type not in request.");
        }
        if ($data[IExportTypeService::REQUEST_KEYS["EXPORT_TYPE"]] === null ||
            $data[IExportTypeService::REQUEST_KEYS["EXPORT_TYPE"]] === "") {
            throw new BadRequestHttpException("Export type is empty.");
        }
        return $data[IExportTypeService::REQUEST_KEYS["EXPORT_TYPE"]];
    }

    public function validateExportRequest($exportType, $data)
    {
        if (!in_array($exportType, IExportTypeService::EXPORT_TYPES)) {
            throw new BadRequestHttpException(
                sprintf("Export type (%s) not allowed.", $data[IExportTypeService::REQUEST_KEYS["EXPORT_TYPE"]])
            );
        }
        if (!array_key_exists(IExportTypeService::REQUEST_KEYS["EXPORT_DATA"], $data)) {
            throw new BadRequestHttpException("Export data not in request.");
        }
        if (!is_array($data[IExportTypeService::REQUEST_KEYS["EXPORT_DATA"]])) {
            throw new BadRequestHttpException("Export data not a valid array.");
        }
        array_walk($data[IExportTypeService::REQUEST_KEYS["EXPORT_DATA"]], function ($item, $key) {
            if (!array_key_exists("id", $item)) {
                throw new BadRequestHttpException(
                    sprintf("Export data error: (id) does not exist in item position (%d)", $key)
                );
            }
        });
    }
}
