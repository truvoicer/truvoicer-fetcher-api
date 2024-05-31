<?php

namespace App\Exceptions;

use App\Services\ApiManager\Client\Entity\ApiRequest;
use Exception;

class OauthResponseException extends Exception
{
    public function __construct(
        $message = "Oauth response error",
        private ?int $statusCode = null,
        private array $data = [],
        private ?ApiRequest $request = null,
    )
    {
        parent::__construct($message);
    }


    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'status_code' => $this->statusCode,
            'message' => $this->getMessage(),
            'data' => $this->data,
            'request' => $this->request->toArray(),
        ], 500);
    }
}
