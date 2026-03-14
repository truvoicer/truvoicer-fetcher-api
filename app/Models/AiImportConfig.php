<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Truvoicer\TfDbReadCore\Models\User;

class AiImportConfig extends Model
{

    protected $fillable = [
        'user_id',
        'label',
        'description',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
