<?php

namespace App\Models;

use App\Repositories\FileDownloadRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileDownload extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'file_downloads';
    public const REPOSITORY = FileDownloadRepository::class;
    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
