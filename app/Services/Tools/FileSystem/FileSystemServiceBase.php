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
    protected array $config;

    public function __construct(FileSystemService $fileSystemService, string $fileSystemName)
    {
        parent::__construct();
        $this->fileSystemService = $fileSystemService;
        $fileSystem = Storage::disk($fileSystemName);
        $this->config = $fileSystem->getConfig();
        $this->setFilesystem($fileSystem);
    }

    public function getFileDownloadUrl(File $file) {
        if (!$this->fileSystemService->createFileDownload($file)) {
            throw new BadRequestHttpException(
                'Error creating file download'
            );
        }
        return $this->buildDownloadUrl($this->fileSystemService->getFileDownloadRepository()->getModel());
    }

    public function buildDownloadUrl(FileDownload $fileDownload) {
        return sprintf(
            '%s/%s',
                $this->config['file_download_url'],
                $fileDownload->download_key
            );
    }

    protected function getRootPath(): string
    {
        return $this->config['root'];
    }

    protected function getFullPath(string $path): string
    {
        if (!str_starts_with('/', $path)) {
            $path = '/' . $path;
        }
        return $this->getRootPath() . $path;
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
