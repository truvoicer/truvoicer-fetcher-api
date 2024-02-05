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
    protected $casts = [
        'response_key_override' => 'boolean',
        'config_override' => 'boolean',
        'parameter_override' => 'boolean',
        'scheduler_override' => 'boolean',
        'rate_limits_override' => 'boolean',
    ];

    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }
}
