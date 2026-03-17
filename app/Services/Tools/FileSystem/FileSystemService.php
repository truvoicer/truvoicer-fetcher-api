<?php

namespace App\Services\Tools\FileSystem;

use App\Models\File;
use App\Models\FileDownload;
use App\Repositories\FileDownloadRepository;
use App\Repositories\FileRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileSystemService
{

    private FileRepository $fileRepository;

    private FileDownloadRepository $fileDownloadRepository;

    public function __construct()
    {
        $this->fileRepository = new FileRepository;
        $this->fileDownloadRepository = new FileDownloadRepository;
    }

    public function findByQuery(string $query)
    {
        return $this->fileRepository->findByQuery($query);
    }

    public function getFileById(int $fileId)
    {
        $file = $this->fileRepository->findById($fileId);
        if ($file === null) {
            throw new BadRequestHttpException(sprintf('FileSystem item id:%s not found in database.',
                $fileId
            ));
        }

        return $file;
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function createFileDownload(File $file, string $clientIp, ?string $userAgent = null): bool
    {
        return $this->fileDownloadRepository->saveFileDownload(
            $file,
            $this->generateRandomString(16),
            $clientIp,
            $userAgent
        );
    }

    public function createFile(
        string $fileName,
        string $fullPath,
        string $relativePath,
        string $fileType,
        string $ext,
        string $mimeType,
        int $fileSize,
        string $fileSystem
    ) {
        return $this->fileRepository->saveFile(
            $fileName,
            $fullPath,
            $relativePath,
            $fileType,
            $ext,
            $mimeType,
            $fileSize,
            $fileSystem
        );
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->fileRepository->setOrderDir($order);
        $this->fileRepository->setSortField($sort);
        $this->fileRepository->setLimit($count);

        return $this->fileRepository->findMany();
    }

    public function deleteFileById(int $fileId)
    {
        $file = $this->fileRepository->findById($fileId);
        if ($file === null) {
            throw new BadRequestHttpException(sprintf('File id: %s not found in database.', $fileId));
        }

        return $this->deleteFile($file);
    }

    public function deleteFile(File $file)
    {
        return $this->fileRepository->deleteFile($file);
    }

    public function deleteFileDownloadById(int $fileDownloadId)
    {
        $fileDownload = $this->fileRepository->findById($fileDownloadId);
        if ($fileDownload === null) {
            throw new BadRequestHttpException(sprintf('File download id: %s not found in database.', $fileDownloadId));
        }

        return $this->fileDownloadRepository->deleteFileDownload($fileDownload);
    }

    public function deleteFileDownload(FileDownload $fileDownload)
    {
        return $this->fileDownloadRepository->deleteFileDownload($fileDownload);
    }

    public function getFileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    public function getFileDownloadRepository(): FileDownloadRepository
    {
        return $this->fileDownloadRepository;
    }
}
