<?php

namespace App\Repositories;

use App\Models\FileDownload;

class FileDownloadRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(FileDownload::class);
    }
}
