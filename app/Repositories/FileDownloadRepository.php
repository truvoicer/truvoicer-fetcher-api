<?php

namespace App\Repositories;

use App\Models\File;
use App\Models\FileDownload;

class FileDownloadRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(FileDownload::class);
    }

    public function getModel(): FileDownload
    {
        return parent::getModel();
    }

    public function findByParams(string $sort, string  $order, ?int $count = null)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function findByQuery($query)
    {
        $this->addWhere('download_key', $query, 'LIKE');
        return $this->findAll();
    }

    public function saveFileDownload(File $file, string $downloadKey, string $clientIp, null|string $userAgent = null): bool
    {
        $save = $file->fileDownloads()->create(
            [
                'download_key' => $downloadKey,
                'client_ip' => $clientIp,
                'user_agent' => $userAgent
            ]
        );
        if (!$save->exists) {
            return false;
        }
        $this->setModel($save);
        return true;
    }

    public function deleteFileDownload(FileDownload $fileDownload) {
        $this->setModel($fileDownload);
        return $this->delete();
    }
}
