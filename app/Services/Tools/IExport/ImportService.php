<?php

namespace App\Services\Tools\IExport;

use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\Uploads\UploadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportService
{
    private HttpRequestService $httpRequestService;
    private SerializerService $serializerService;
    private DownloadsFileSystemService $downloadsFileSystem;
    private UploadsFileSystemService $uploadsFileSystemService;
    private IExportTypeService $iExportTypeService;

    public function __construct(
        SerializerService $serializerService,
        DownloadsFileSystemService $downloadsFileSystemService,
        HttpRequestService $httpRequestService,
        UploadsFileSystemService $uploadsFileSystemService,
        IExportTypeService $iExportTypeService
    ) {
        $this->iExportTypeService = $iExportTypeService;
        $this->httpRequestService = $httpRequestService;
        $this->uploadsFileSystemService = $uploadsFileSystemService;
        $this->serializerService = $serializerService;
        $this->downloadsFileSystem = $downloadsFileSystemService;
    }

    private function getXmlData(string $filePath)
    {
        return $this->uploadsFileSystemService->readTempFile($filePath);
    }

    public function runMappingsImporter(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $getFileData = $this->uploadsFileSystemService->fileSystemService->getFileById((int)$requestData["file_id"]);
        $getFileContents = $this->getXmlData($getFileData->path);

        $runImportForType = $this->iExportTypeService->runImportForType(
            $requestData["import_type"],
            $getFileContents,
            $requestData["mappings"]
        );
        return array_map(function ($item) {
            if (is_array($item) && isset($item["status"]) && $item["status"] === "error") {
                return $item;
            }
            if ($item === null) {
                return [
                    "status" => "error",
                    "message" => "Import error: Please try again."
                ];
            }
            return [
                "status" => "success",
                "message" => "Import successful"
            ];
        }, $runImportForType);
    }

    public function runImporter(UploadedFile $uploadedFile)
    {
        $storeFile = $this->uploadsFileSystemService->getUploadedFilePath($uploadedFile);
        $saveFile = $this->uploadsFileSystemService->saveUploadTempFileToDatabase($storeFile, "import", "xml");

        $getFileContents = $this->getXmlData($storeFile);

        $getImportDataMappings = $this->iExportTypeService->getImportDataMappings(
            $validateData["import_type"],
            $getFileContents
        );
        if (count($getImportDataMappings) > 0) {
            return [
                "mappings" => $getImportDataMappings,
                "file" => $this->serializerService->entityToArray($saveFile, ["single"])
            ];
        }

        $runImportForType = $this->iExportTypeService->runImportForType($validateData["import_type"], $getFileContents);

        return array_map(function ($item) {
            if (is_array($item) && isset($item["status"]) && $item["status"] === "error") {
                return $item;
            }
            if ($item === null) {
                return [
                    "status" => "error",
                    "message" => "Import error: Please try again."
                ];
            }
            return [
                "status" => "success",
                "message" => "Import successful"
            ];
        }, $runImportForType);
    }

}
