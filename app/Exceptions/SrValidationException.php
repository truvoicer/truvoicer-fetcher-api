<?php

namespace App\Exceptions;

use App\Services\ApiManager\Client\Entity\ApiRequest;
use Exception;

class SrValidationException extends Exception
{
    public function __construct(
        $message = "Error validating response keys",
        private ?array $errors = [],
    )
    {
        parent::__construct($message);
    }


    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], 500);
    }
}
