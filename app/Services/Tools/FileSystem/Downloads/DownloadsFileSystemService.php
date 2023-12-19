<?php

namespace App\Services\Tools\FileSystem\Downloads;

use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\FileSystem\FileSystemServiceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DownloadsFileSystemService extends FileSystemServiceBase
{
    const FILE_SYSTEM_NAME = "downloads";


    public function __construct(FileSystemService $fileSystemService)
    {
        parent::__construct($fileSystemService, self::FILE_SYSTEM_NAME);
    }

    /**
     * @param string $dir
     * @param string $fileName
     * @param string $fileContents
     */
    public function storeNewDownloadsFile(string $dir, string $fileName, string $fileContents)
    {
        if ($this->filesystem->exists($dir)) {
            return $this->filesystem->update($dir, $fileContents);
        } else {
            return $this->filesystem->write($dir, $fileContents);
        }
    }

    public function saveDownloadsFileToDatabase( string $dir, string $fileName, string $fileType, string $ext ) {
        $saveToDatabase = $this->fileSystemService->createFile([
            "file_name" => $fileName,
            "file_path" => $dir,
            "file_type" => $fileType,
            "file_extension" => $ext,
//            "mime_type" => $this->filesystem->getMimetype($dir),
//            "file_size" => $this->filesystem->getSize($dir),
            "file_system" => self::FILE_SYSTEM_NAME,
        ]);
        if (!$saveToDatabase || $saveToDatabase === null) {
            return false;
        }
        return $saveToDatabase;
    }

    public function readFileStream(string $path) {
        $resource = $this->filesystem->readStream($path);
        if ($resource === false) {
            throw new BadRequestHttpException(sprintf("Error opening file stream for path: (%s)", $path));
        }
        return $resource;
    }
}
