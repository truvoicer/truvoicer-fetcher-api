<?php
namespace App\Services\Tools\FileSystem\Uploads;

use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\FileSystem\FileSystemServiceBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadsFileSystemService extends FileSystemServiceBase
{
    const FILE_SYSTEM_NAME = "uploads";

    public function __construct(FileSystemService $fileSystemService) {
        parent::__construct($fileSystemService, self::FILE_SYSTEM_NAME);
    }

    public function getUploadedFilePath(UploadedFile $uploadedFile) {

        $filesystem = new Filesystem();
        $copyToPath = $this->uploadTempDir . "/" . $uploadedFile->getFileName() . ".xml";
        try {
            $filesystem->copy($uploadedFile->getRealPath(), $copyToPath);
            return $uploadedFile->getFileName() . ".xml";
        } catch (\Exception $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
            return false;
        }
    }

    public function saveUploadTempFileToDatabase(string $fileName, string $fileType, string $ext ) {
        $saveToDatabase = $this->fileSystemService->createFile([
            "file_name" => $fileName,
            "file_path" => $fileName,
            "file_type" => $fileType,
            "file_extension" => $ext,
            "mime_type" => $this->uploadTempFilesystem->getMimetype( $fileName ),
            "file_size" => $this->uploadTempFilesystem->getSize( $fileName ),
            "file_system" => self::FILE_SYSTEM_NAME,
        ]);
        if (!$saveToDatabase || $saveToDatabase === null) {
            return false;
        }
        return $saveToDatabase;
    }

    public function readTempFile($filePath) {
        return $this->uploadTempFilesystem->read($filePath);
    }

    public function readFileStream(string $path) {
        $resource = $this->uploadTempFilesystem->readStream($path);
        if ($resource === false) {
            throw new BadRequestHttpException(sprintf("Error opening file stream for path: (%s)", $path));
        }
        return $resource;
    }
}
