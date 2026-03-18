<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Http\Resources\BaseCollection;

class AiImportConfigCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public static $wrap = 'ai_import_configs';

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
