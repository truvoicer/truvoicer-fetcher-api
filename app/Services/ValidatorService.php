<?php

namespace App\Services;

use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Provider\ProviderService;
use Illuminate\Support\Facades\Route;
use \Illuminate\Routing\Route as RoutingRoute;

class ValidatorService extends BaseService
{
    private ProviderService $providerService;
    public function __construct(ProviderService $providerService)
    {
        parent::__construct();
        $this->providerService = $providerService;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAllProviderData(User $user): array
    {
        if (
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        ) {
            $providers = $this->providerService->getProviderList();
        } else {
            $providers = $this->providerService->findUserProviders($user);
        }

        if (!$this->responseKeysService->createDefaultServiceResponseKeys($service)) {
            return $this->sendErrorResponse("Error loading default service response keys");
        }
        $getServices = $this->requestService->getUserServiceRequestByProvider(
            $provider,
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );

        if (!$this->srResponseKeyService->validateSrResponseKeys($serviceRequest, true)) {
            return $this->sendErrorResponse("Error validating response keys");
        }

        if (!$this->requestConfigService->requestConfigValidator($serviceRequest, true)) {
            return $this->sendErrorResponse("Error validating request config");
        }
    }
}
