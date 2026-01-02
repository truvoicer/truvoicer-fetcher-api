<?php

namespace App\Http\Controllers;

use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected AccessControlService $accessControlService;

    public function __construct()
    {
        $this->accessControlService = app(AccessControlService::class);
    }

    protected function setAccessControlUser(?User $user = null) {
        if ($user instanceof User) {
            $this->accessControlService->setUser($user);
        }
    }

    protected function sendErrorResponse(string $message, ?array $data = [], ?array $errors = [], ?int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR) {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $statusCode);
    }
    protected function sendSuccessResponse(string $message, $data = [], ?array $errors = [], ?int $statusCode = Response::HTTP_OK) {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $statusCode);
    }
}
