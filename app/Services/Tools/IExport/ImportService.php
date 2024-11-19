<?php

namespace App\Services\Tools\IExport;

use App\Exceptions\ImportException;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\Imports\ImportsFileSystemService;
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
        private ImporterValidator $importerValidator,
        private ImportsFileSystemService $importsFileSystemService
    ) {
    }

    private function getXmlData(string $filePath)
    {
        return $this->uploadsFileSystemService->readTempFile($filePath);
    }

    public function runMappingsImporter(int $fileId, array $mappings)
    {
        $getFileData = $this->importsFileSystemService->fileSystemService->getFileById($fileId);
        $getFileContents = $this->importsFileSystemService->getFilesystem()->get($getFileData->rel_path);
        if (!$getFileContents) {
            throw new BadRequestHttpException("Error reading file");
        }
        $runImportForType = $this->iExportTypeService->runImportForType(
            json_decode($getFileContents, true),
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
        $contents = $uploadedFile->getContent();
        $getFileContents = json_decode($contents, true);
        if (!$getFileContents) {
            throw new ImportException("Error parsing file");
        }

        $this->importerValidator->validate($getFileContents);
        if ($this->importerValidator->hasErrors()) {
            throw new ImportException(
                "Error validating file",
                $this->importerValidator->getErrors());
        }

        $this->iExportTypeService->validateTypeBatch($getFileContents);
        if ($this->iExportTypeService->hasErrors()) {
            $this->setErrors(array_merge(
                $this->getErrors(),
                $this->iExportTypeService->getErrors()
            ));
        }

        $fileName = sprintf("import-%s.json", (new \DateTime())->format("YmdHis"));
        $fileDirectory = sprintf("exports/%s", $fileName);

        $store = $this->importsFileSystemService->storeNewFile(
            $fileName,
            $fileName,
            $contents
        );
        if (!$store) {
            throw new ImportException("Error storing file");
        }

        $getSavedData = $this->importsFileSystemService->saveToDatabase(
            $fileName,
            $fileName,
            "export",
            "json"
        );
        if (!$getSavedData) {
            throw new ImportException(
                "Export save item error: Unable to save item to database."
            );
        }

        return [
            'file' => $getSavedData,
            'config' => $this->iExportTypeService::getImporterConfigs(
                $this->iExportTypeService::IMPORTERS
            ),
            "data" => $this->iExportTypeService->filterImportData($getFileContents)
        ];
    }

}
