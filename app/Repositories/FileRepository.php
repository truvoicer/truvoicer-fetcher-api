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
    public function findByParams(string $sort, string  $order, ?int $count = null)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function findByQuery($query)
    {
        $this->addWhere('filename', $query, 'LIKE');
        return $this->findAll();
    }

    public function saveFile(
        string $fileName,
        string $fullPath,
        string $relativePath,
        string $fileType,
        string $ext,
        string $mimeType,
        int $fileSize,
        string $fileSystem
    )
    {
        return $this->save(
            [
                "filename" => $fileName,
                "full_path" => $fullPath,
                "rel_path" => $relativePath,
                "type" => $fileType,
                "extension" => $ext,
                "mime_type" => $mimeType,
                "size" => $fileSize,
                "file_system" => $fileSystem
            ]
        );
    }

    public function deleteFile(File $fileSystemItem) {
        $this->setModel($fileSystemItem);
        return $this->delete();
    }
}
