<?php

namespace App\Http\Controllers\Api\Backend\Tools\Encoding;

use Truvoicer\TruFetcherGet\Enums\MbEncoding;
use App\Http\Controllers\Controller;
class MbEncodingController extends Controller
{

    public function index()
    {
        return $this->sendSuccessResponse(
            "success",
            array_map(
                fn(MbEncoding $mbEncoding) => $mbEncoding->value,
                MbEncoding::cases()
            )
        );
    }

}
