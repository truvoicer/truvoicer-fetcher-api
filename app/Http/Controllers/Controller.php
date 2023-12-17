<?php

namespace App\Http\Controllers;

use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected SerializerService $serializerService;
    protected HttpRequestService $httpRequestService;
    protected AccessControlService $accessControlService;

    public function __construct(
        AccessControlService $accessControlService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        Request $request
    )
    {
        $this->serializerService = $serializerService;
        $this->httpRequestService = $httpRequestService;
        $this->accessControlService = $accessControlService;
        $this->accessControlService->setUser($request->user());
    }
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
