<?php
namespace App\Services\Tools\FileSystem;

use App\Models\File;
use App\Models\FileDownload;
use App\Services\BaseService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FileSystemServiceBase extends BaseService
{
    const FILE_DOWNLOAD_ROOT_PATH = "/files/download/file/%s";
    protected string $projectDir;
    protected string $uploadTempDir;
    public FileSystemService $fileSystemService;
    protected ParameterBagInterface $parameterBag;

    public function __construct(string $projectDir, string $uploadTempDir, FileSystemService $fileSystemService,
                                ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->projectDir = $projectDir;
        $this->uploadTempDir = $uploadTempDir;
        $this->fileSystemService = $fileSystemService;
        $this->parameterBag = $parameterBag;
    }

    public function getFileDownloadUrl(File $file) {
        $createFileDownload = $this->fileSystemService->createFileDownload($file);
        return $this->buildDownloadUrl($createFileDownload);
    }

    protected function buildDownloadUrl(FileDownload $fileDownload) {
        return $this->parameterBag->get("app.base_url") . sprintf(
            self::FILE_DOWNLOAD_ROOT_PATH, $fileDownload->getDownloadKey()
            );
    }
}
