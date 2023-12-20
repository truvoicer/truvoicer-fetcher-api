<?php

namespace App\Services\Tools\FileSystem;

use App\Models\File;
use App\Models\FileDownload;
use App\Repositories\FileDownloadRepository;
use App\Repositories\FileRepository;
use App\Services\Tools\HttpRequestService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileSystemService
{

    private HttpRequestService $httpRequestService;
    private FileRepository $fileRepository;
    private FileDownloadRepository $fileDownloadRepository;

    public function __construct(HttpRequestService $httpRequestService)
    {
        $this->httpRequestService = $httpRequestService;
        $this->fileRepository = new FileRepository();
        $this->fileDownloadRepository = new FileDownloadRepository();
    }

    public function findByQuery(string $query)
    {
        return $this->fileRepository->findByQuery($query);
    }

    public function getFileById(int $fileId) {
        $file = $this->fileRepository->findById($fileId);
        if ($file === null) {
            throw new BadRequestHttpException(sprintf("FileSystem item id:%s not found in database.",
                $fileId
            ));
        }
        return $file;
    }

    private function getFileObject(array $data) {
        $fileData = [];
        $fileData['file_name'] = $data['filename'];
        $fileData['file_path'] = $data['path'];
        $fileData['file_type'] = $data['file_type'];
        $fileData['mime_type'] = $data['mime_type'];
        $fileData['file_extension'] = $data['extension'];
        $fileData['file_size'] = $data['file_size'];
        $fileData['file_system'] = $data['file_system'];
        return $fileData;
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function getFileDownloadObject(File $file) {
        $data = [];
        $data['file_id'] = $file->id;
        $data['download_key'] = $this->generateRandomString(16);
        return $data;
    }

    public function createFileDownload(File $file)
    {
        $fileDownload = $this->getFileDownloadObject($file);
        return $this->fileDownloadRepository->saveFileDownload($fileDownload);
    }

    public function createFile(array $data)
    {
        $file = $this->getFileObject($data);
        return $this->fileRepository->saveFile($file);
    }

    public function updateFile(array $data)
    {
        $file = $this->fileRepository->findById($data["id"]);
        if ($file === null) {
            throw new BadRequestHttpException(sprintf("File id:%d not found in database.", $data["id"]));
        }
        $fileData = $this->getFileObject($data);
        $this->fileRepository->setModel($file);
        return $this->fileRepository->save($fileData);
    }

    public function findByParams(string $sort, string  $order, int $count) {
        $this->fileRepository->setOrderBy($order);
        $this->fileRepository->setSort($sort);
        $this->fileRepository->setLimit($count);
        return $this->fileRepository->findMany();
    }

    public function deleteFileById(int $fileId) {
        $file = $this->fileRepository->findById($fileId);
        if ($file === null) {
            throw new BadRequestHttpException(sprintf("File id: %s not found in database.", $fileId));
        }
        return $this->deleteFile($file);
    }
    public function deleteFile(File $file) {
        return $this->fileRepository->deleteFile($file);
    }

    public function deleteFileDownloadById(int $fileDownloadId) {
        $fileDownload = $this->fileRepository->findById($fileDownloadId);
        if ($fileDownload === null) {
            throw new BadRequestHttpException(sprintf("File download id: %s not found in database.", $fileDownloadId));
        }
        return $this->fileDownloadRepository->deleteFileDownload($fileDownload);
    }

    public function deleteFileDownload(FileDownload $fileDownload) {
        if ($fileDownload === null) {
            return false;
        }
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
