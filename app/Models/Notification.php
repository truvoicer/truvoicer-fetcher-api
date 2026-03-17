<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $type
 * @property string notifiable_type
 * @property int notifiable_id
 * @property array data
 * @property \Carbon\Carbon|null read_at
 */
class Notification extends Model
{
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
