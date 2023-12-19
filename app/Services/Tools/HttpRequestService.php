<?php
namespace App\Services\Tools;

use Symfony\Component\HttpFoundation\Request;

class HttpRequestService
{

    public function __construct()
    {
    }

    public function getRequestData(Request $request, $array = false) {
        if ($request->getContentType() == "json") {
            return json_decode($request->getContent(), $array);
        }
        return $request->request->all();
    }

    public function validateData($entity) {
        return true;
    }
}
