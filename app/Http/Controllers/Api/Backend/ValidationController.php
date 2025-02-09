<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Services\Permission\AccessControlService;
use App\Services\ValidatorService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

class ValidationController extends Controller
{

    public function __construct(
        private ValidatorService $validatorService,
    ) {
        parent::__construct();
    }

    public function validateAll(Request $request)
    {
        $user = $request->user();
        if (!$this->validatorService->validateAllProviderData($user)) {
            return $this->sendErrorResponse(
                "Validation failed"
            );
        }
        return $this->sendSuccessResponse(
            "Validation check successful",
            [
                'errors' => $this->validatorService->getErrors()
            ]
        );
    }
}
