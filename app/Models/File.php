<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'files';
    public function fileDownload()
    {
        return $this->hasMany(FileDownload::class);
    }
}
