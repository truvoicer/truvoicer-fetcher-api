<?php

namespace App\Http\Controllers\Api\Backend\Tools\Format;

use Truvoicer\TruFetcherGet\Enums\FormatOptions;
use App\Http\Controllers\Controller;
class FormatOptionController extends Controller
{

    public function index()
    {
        return $this->sendSuccessResponse(
            "success",
            FormatOptions::labelAndValueArray()
        );
    }

}
