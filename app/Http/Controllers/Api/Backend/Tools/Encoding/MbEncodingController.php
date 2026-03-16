<?php

namespace App\Http\Controllers\Api\Backend\Tools\Encoding;

use App\Http\Controllers\Controller;
use Truvoicer\TfDbReadCore\Enums\MbEncoding;

class MbEncodingController extends Controller
{
    public function index()
    {
        return $this->sendSuccessResponse(
            'success',
            array_map(
                fn (MbEncoding $mbEncoding) => $mbEncoding->value,
                MbEncoding::cases()
            )
        );
    }
}
