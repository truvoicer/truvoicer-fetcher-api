<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileDownload extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'file_downloads';
    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
