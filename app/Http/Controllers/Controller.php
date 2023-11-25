<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    protected function sendErrorResponse(string $message, ?array $data = [], ?array $errors = [], ?int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR) {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $statusCode);
    }
    protected function sendSuccessResponse(string $message, $data = [], ?array $errors = [], ?int $statusCode = Response::HTTP_OK) {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $statusCode);
    }
}
