<?php

namespace App\Services\Tools\IExport;

use App\Exceptions\ImportException;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\Uploads\UploadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Traits\Error\ErrorTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportService
{
    use ErrorTrait;

    public function __construct(
        private SerializerService $serializerService,
        private DownloadsFileSystemService $downloadsFileSystemService,
        private HttpRequestService $httpRequestService,
        private UploadsFileSystemService $uploadsFileSystemService,
        private IExportTypeService $iExportTypeService,
        private ImporterValidator $importerValidator
    ) {
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

    public function parseImport(UploadedFile $uploadedFile)
    {
        $getFileContents = json_decode($uploadedFile->getContent(), true);
        if (!$getFileContents) {
            throw new ImportException("Error parsing file");
        }

        $validateData = $this->importerValidator->validate($getFileContents);
        if ($this->importerValidator->hasErrors()) {
            throw new ImportException(
                "Error validating file",
                $this->importerValidator->getErrors());
        }
        foreach ($getFileContents as $item) {
            if (!$this->iExportTypeService->validateType($item["type"], $item['data'])) {
                throw new ImportException(
                    "Import type error",
                    $this->iExportTypeService->getErrors()
                );
            }
        }
        dd(true);
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
