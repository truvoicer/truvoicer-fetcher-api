<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RouteResource;
use App\Services\Auth\AuthService;
use App\Services\Permission\AccessControlService;
use App\Services\Provider\ProviderService;
use App\Services\ValidatorService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ValidationController extends Controller
{
    private ValidatorService $validatorService;

    /**
     * ProviderController constructor.
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     * @param ValidatorService $providerService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        ValidatorService $providerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->validatorService = $providerService;
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
