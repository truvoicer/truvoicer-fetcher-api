<?php

namespace App\Repositories;

use App\Models\FileDownload;

class FileDownloadRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(FileDownload::class);
    }
    public function findByParams(string $sort, string  $order, int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function findByQuery($query)
    {
        $this->addWhere('download_key', $query, 'LIKE');
        return $this->findAll();
    }

    public function saveFileDownload(array $data)
    {
        return $this->save($data);
    }

    public function deleteFileDownload(FileDownload $fileDownload) {
        $this->setModel($fileDownload);
        return $this->delete();
    }
}
