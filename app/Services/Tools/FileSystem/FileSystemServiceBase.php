<?php
namespace App\Services\Tools\FileSystem;

use App\Models\File;
use App\Models\FileDownload;
use App\Services\BaseService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileSystemServiceBase extends BaseService
{
    const FILE_DOWNLOAD_ROOT_PATH = "/files/download/file/%s";
    protected string $fileSystemName;
    protected Filesystem $filesystem;
    public FileSystemService $fileSystemService;

    public function __construct(FileSystemService $fileSystemService, string $fileSystemName)
    {
        $this->fileSystemService = $fileSystemService;
        $this->setFilesystem(Storage::disk($fileSystemName));
    }

    public function getFileDownloadUrl(File $file) {
        if (!$this->fileSystemService->createFileDownload($file)) {
            throw new BadRequestHttpException(
                'Error creating file download'
            );
        }
        return $this->buildDownloadUrl($this->fileSystemService->getFileDownloadRepository()->getModel());
    }

    protected function buildDownloadUrl(FileDownload $fileDownload) {
        return env('SITE_BASE_URL') . sprintf(
            self::FILE_DOWNLOAD_ROOT_PATH, $fileDownload->download_key
            );
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }
}
