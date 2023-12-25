<?php

namespace App\Repositories;

use App\Models\File;

class FileRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(File::class);
    }

    public function getModel(): File
    {
        return parent::getModel();
    }
    public function findByParams(string $sort, string  $order, int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function findByQuery($query)
    {
        $this->addWhere('filename', $query, 'LIKE');
        return $this->findAll();
    }

    public function saveFile(array $data)
    {
        return $this->save($data);
    }

    public function deleteFile(File $fileSystemItem) {
        $this->setModel($fileSystemItem);
        return $this->delete();
    }
}
