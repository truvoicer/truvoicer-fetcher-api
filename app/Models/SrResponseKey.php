<?php

namespace App\Models;

use App\Repositories\SrResponseKeyRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrResponseKey extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'sr_response_keys';
    public const REPOSITORY = SrResponseKeyRepository::class;

    protected $fillable = [
        "value",
        "show_in_response",
        "list_item",
        "custom_value",
        "searchable",
        "is_date",
        "date_format",
        "append_extra_data_value",
        "prepend_extra_data_value",
        "is_service_request",
        "array_keys",
    ];
    protected $casts = [
        'show_in_response' => 'boolean',
        'list_item' => 'boolean',
        'custom_value' => 'boolean',
        'searchable' => 'boolean',
        'is_date' => 'boolean',
        'date_format' => 'string',
        'append_extra_data_value' => 'string',
        'prepend_extra_data_value' => 'string',
        'is_service_request' => 'boolean',
    ];
    public function sr()
    {
        return $this->belongsTo(Sr::class);
    }
    public function sResponseKey()
    {
        return $this->belongsTo(SResponseKey::class);
    }
    public function sResponseKeySr()
    {
        return $this->hasMany(SResponseKey::class);
    }

    public function srResponseKeySrs()
    {
        return $this->belongsToMany(
            Sr::class,
            SrResponseKeySr::TABLE_NAME,
            'sr_response_key_id',
            'sr_id'
        )
            ->withPivot('response_response_keys', 'request_response_keys', 'action', 'single_request', 'disable_request');
    }

    public function entityLock()
    {
        return $this->morphMany(EntityLock::class, 'entity');
    }
}
