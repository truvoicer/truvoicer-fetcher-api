<?php

namespace App\Models;

use App\Repositories\SrScheduleRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrSchedule extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'sr_schedules';
    public const REPOSITORY = SrScheduleRepository::class;
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
