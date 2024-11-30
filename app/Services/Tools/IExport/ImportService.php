<?php

namespace App\Services\Tools\IExport;

use App\Exceptions\ImportException;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\Imports\ImportsFileSystemService;
use App\Services\Tools\FileSystem\Uploads\UploadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\Importer\Entities\PropertyImporterService;
use App\Services\Tools\SerializerService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportService
{
    use ErrorTrait, UserTrait;

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
        $this->iExportTypeService->setUser($this->getUser());
        $getFileData = $this->importsFileSystemService->fileSystemService->getFileById($fileId);
        $getFileContents = $this->importsFileSystemService->getFilesystem()->get($getFileData->rel_path);
        if (!$getFileContents) {
            throw new BadRequestHttpException("Error reading file");
        }
        return $this->iExportTypeService->runImportForType(
            json_decode($getFileContents, true),
            $mappings
        );
    }

    public function parseImport(UploadedFile $uploadedFile)
    {
        $this->iExportTypeService->setUser($this->getUser());
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
