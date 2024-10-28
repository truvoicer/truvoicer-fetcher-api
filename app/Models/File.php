<?php

namespace App\Models;

use App\Repositories\FileRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'files';
    public const REPOSITORY = FileRepository::class;

    protected $fillable = [
        'filename',
        'full_path',
        'rel_path',
        'extension',
        'type',
        'size',
        'file_system',
        'mime_type'
    ];

    public function fileDownloads()
    {
        return $this->hasMany(FileDownload::class);
    }
}
