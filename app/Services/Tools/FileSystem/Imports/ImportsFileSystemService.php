<?php

namespace App\Services\Tools\FileSystem\Imports;

use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\FileSystem\FileSystemServiceBase;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportsFileSystemService extends FileSystemServiceBase
{
    const FILE_SYSTEM_NAME = "imports";


    public function __construct(FileSystemService $fileSystemService)
    {
        parent::__construct($fileSystemService, self::FILE_SYSTEM_NAME);
    }

    /**
     * @param string $dir
     * @param string $fileName
     * @param string $fileContents
     */
    public function storeNewFile(string $dir, string $fileName, string $fileContents): bool
    {
        return $this->filesystem->put($dir, $fileContents);
    }

    public function saveToDatabase( string $dir, string $fileName, string $fileType, string $ext ) {
        $fullPath = $this->getFullPath($dir);
        $saveToDatabase = $this->fileSystemService->createFile(
            $fileName,
            $fullPath,
            $dir,
            $fileType,
            $ext,
            File::mimeType($fullPath),
            File::size($fullPath),
            self::FILE_SYSTEM_NAME
        );
        if (!$saveToDatabase) {
            return false;
        }
        return $this->fileSystemService->getFileRepository()->getModel();
    }

    public function readFileStream(string $path) {
        $resource = $this->filesystem->readStream($path);
        if (!$resource) {
            throw new BadRequestHttpException(sprintf("Error opening file stream for path: (%s)", $path));
        }
        return $resource;
    }
}
