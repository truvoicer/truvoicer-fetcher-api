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
        "is_date",
        "date_format",
        "append_extra_data_value",
        "prepend_extra_data_value",
        "is_service_request",
        "has_array_value",
        "array_keys",
        "return_data_type"
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
            ->withPivot('response_response_keys', 'request_response_keys', 'action', 'single_request', 'disable_request')
            ->using(SrResponseKeySr::class);
    }
}
