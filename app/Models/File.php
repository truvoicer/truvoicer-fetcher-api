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
    public function fileDownload()
    {
        return $this->hasMany(FileDownload::class);
    }
}
