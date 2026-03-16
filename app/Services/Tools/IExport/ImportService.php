<?php

namespace App\Services\Tools\IExport;

use App\Exceptions\ImportException;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\Imports\ImportsFileSystemService;
use App\Services\Tools\FileSystem\Uploads\UploadsFileSystemService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Truvoicer\TfDbReadCore\Traits\Error\ErrorTrait;
use Truvoicer\TfDbReadCore\Traits\User\UserTrait;

class ImportService
{
    use ErrorTrait, UserTrait;

    public function __construct(
        private DownloadsFileSystemService $downloadsFileSystemService,
        private UploadsFileSystemService $uploadsFileSystemService,
        private IExportTypeService $iExportTypeService,
        private ImporterValidator $importerValidator,
        private ImportsFileSystemService $importsFileSystemService
    ) {}

    public function import(int $fileId, array $mappings)
    {
        $this->iExportTypeService->setUser($this->getUser());
        $getFileData = $this->importsFileSystemService->fileSystemService->getFileById($fileId);

        $getFileContents = $this->importsFileSystemService->getFilesystem()->get($getFileData->rel_path);
        if (! $getFileContents) {
            throw new BadRequestHttpException('Error reading file');
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
        if (! $getFileContents) {
            throw new ImportException('Error parsing file');
        }

        $this->importerValidator->validate($getFileContents);
        if ($this->importerValidator->hasErrors()) {
            throw new ImportException(
                'Error validating file',
                $this->importerValidator->getErrors());
        }

        $this->iExportTypeService->validateTypeBatch($getFileContents);
        if ($this->iExportTypeService->hasErrors()) {
            $this->setErrors(array_merge(
                $this->getErrors(),
                $this->iExportTypeService->getErrors()
            ));
        }

        $fileName = sprintf('import-%s.json', (new \DateTime)->format('YmdHis'));
        $store = $this->importsFileSystemService->storeNewFile(
            $fileName,
            $fileName,
            $contents
        );
        if (! $store) {
            throw new ImportException('Error storing file');
        }

        $getSavedData = $this->importsFileSystemService->saveToDatabase(
            $fileName,
            $fileName,
            'export',
            'json'
        );
        if (! $getSavedData) {
            throw new ImportException(
                'Export save item error: Unable to save item to database.'
            );
        }

        return [
            'file' => $getSavedData,
            'config' => $this->iExportTypeService::getImporterConfigs(
                $this->iExportTypeService::IMPORTERS
            ),
            'data' => $this->iExportTypeService->filterImportData($getFileContents),
        ];
    }

    public function lockEntities(int $fileId, array $mappings)
    {

        $this->iExportTypeService->setUser($this->getUser());
        $getFileData = $this->importsFileSystemService->fileSystemService->getFileById($fileId);

        $getFileContents = $this->importsFileSystemService->getFilesystem()->get($getFileData->rel_path);
        if (! $getFileContents) {
            throw new BadRequestHttpException('Error reading file');
        }

        return $this->iExportTypeService->runLock(
            json_decode($getFileContents, true),
            $mappings
        );
    }
}
