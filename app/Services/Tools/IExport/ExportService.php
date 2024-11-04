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
        return IExportTypeService::getImporterConfigs(
            IExportTypeService::IMPORTERS
        );
    }

    public function getExportEntityListData(User $user)
    {
        $this->accessControlService->setUser($user);
        $this->iExportTypeService->setUser($user);
        return $this->iExportTypeService->getImporterExportData(
            IExportTypeService::IMPORTERS,
            ['show' => true]
        );

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

    public function storeXmlDataFromArray(array $xmlArray, string $fullDir, string $fileName)
    {
        return $this->downloadsFileSystem->storeNewDownloadsFile(
            $fullDir,
            $fileName,
            json_encode($xmlArray)
        );
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
        if (!in_array($exportType, IExportTypeService::getExportTypes())) {
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
            if (!is_array($item) || !array_key_exists("id", $item)) {
                throw new BadRequestHttpException(
                    sprintf("Export data error: (id) does not exist in item position (%d)", $key)
                );
            }
        });
    }

    public function getDownloadsFileSystem(): DownloadsFileSystemService
    {
        return $this->downloadsFileSystem;
    }

}
