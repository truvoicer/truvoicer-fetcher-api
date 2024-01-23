<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrSchedule extends Model
{
    use HasFactory;

    const FIELDS = [
        'execute_immediately',
        'forever',
        'disabled',
        'locked',
        'priority',
        'start_date',
        'end_date',
        'use_cron_expression',
        'cron_expression',
        'every_minute',
        'minute',
        'every_hour',
        'hour',
        'every_day',
        'day',
        'every_weekday',
        'weekday',
        'every_month',
        'month',
        'arguments',
    ];

    protected $fillable = self::FIELDS;

    public function sr()
    {
        return $this->belongsTo(Sr::class, 'sr_id', 'id');
    }
}
