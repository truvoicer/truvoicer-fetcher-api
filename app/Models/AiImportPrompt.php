<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use Truvoicer\TfDbReadCore\Models\User;

class AiImportPrompt extends Model
{
    protected $fillable = [
        'user_id',
        'prompt',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
