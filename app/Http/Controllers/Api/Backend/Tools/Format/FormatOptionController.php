<?php

namespace App\Http\Controllers\Api\Backend\Tools\Format;

use App\Http\Controllers\Controller;
use Truvoicer\TfDbReadCore\Enums\FormatOptions;

class FormatOptionController extends Controller
{
    public function index()
    {
        return $this->sendSuccessResponse(
            'success',
            FormatOptions::labelAndValueArray()
        );
    }
}
