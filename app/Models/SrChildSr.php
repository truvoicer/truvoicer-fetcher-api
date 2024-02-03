<?php

namespace App\Models;

use App\Repositories\SrChildSrRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrChildSr extends Model
{
    use HasFactory;
    public const TABLE_NAME = 'sr_child_srs';
    public const REPOSITORY = SrChildSrRepository::class;

    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }
}
