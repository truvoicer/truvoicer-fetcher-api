<?php
namespace App\Services\Tools\FileSystem\Uploads;

use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\FileSystem\FileSystemServiceBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File as FileFacade;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadsFileSystemService extends FileSystemServiceBase
{
    const FILE_SYSTEM_NAME = "uploads";

    public function __construct(FileSystemService $fileSystemService) {
        parent::__construct($fileSystemService, self::FILE_SYSTEM_NAME);
    }

    public function getUploadedFilePath(UploadedFile $uploadedFile) {

        try {
            return $uploadedFile->move($this->getRootPath(), $uploadedFile->getFileName());
        } catch (\Exception $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
            return false;
        }
    }

    public function saveUploadTempFileToDatabase(File $fileName, string $fileType, string $ext ): Model|bool
    {
        $fullPath = $this->getFullPath($fileName->getFilename());
        $saveToDatabase = $this->fileSystemService->createFile(
            $fileName,
            $fullPath,
            $fileName,
            $fileType,
            $ext,
            FileFacade::mimeType($fullPath),
            FileFacade::size($fullPath),
            self::FILE_SYSTEM_NAME
        );
        if (!$saveToDatabase) {
            return false;
        }
        return $this->fileSystemService->getFileRepository()->getModel();
    }

    public function readTempFile($filePath) {
        return $this->filesystem->get($filePath);
    }

    public function readFileStream(string $path) {
        $resource = $this->filesystem->readStream($path);
        if ($resource === false) {
            throw new BadRequestHttpException(sprintf("Error opening file stream for path: (%s)", $path));
        }
        return $resource;
    }
}
