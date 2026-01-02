<?php

namespace App\Services;

use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TfDbReadCore\Services\ApiServices\SResponseKeysService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use Truvoicer\TfDbReadCore\Traits\Error\ErrorTrait;
use Truvoicer\TfDbReadCore\Services\BaseService;

class ValidatorService extends BaseService
{
    use ErrorTrait;
    private ProviderService $providerService;
    private SrConfigService $requestConfigService;
    private SrResponseKeyService $srResponseKeyService;
    private SResponseKeysService $responseKeysService;
    public function __construct(
        ProviderService $providerService,
        SrConfigService $requestConfigService,
        SrResponseKeyService $srResponseKeyService,
        SResponseKeysService $responseKeysService
    )
    {
        parent::__construct();
        $this->providerService = $providerService;
        $this->requestConfigService = $requestConfigService;
        $this->srResponseKeyService = $srResponseKeyService;
        $this->responseKeysService = $responseKeysService;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAllProviderData(User $user): bool
    {
        $providers = $this->providerService->findProviders($user);

        foreach ($providers as $provider) {
            $serviceRequests = $provider->serviceRequest()->get();
            foreach ($serviceRequests as $serviceRequest) {
                $service = $serviceRequest->s()->first();
                if (!$service) {
                    $this->addError(
                        'validator_error',
                        sprintf("Service not found for service request: %s", $serviceRequest->name)
                    );
                    continue;
                }
                if (!$this->responseKeysService->createDefaultServiceResponseKeys($service)) {
                    $this->addError(
                        'validator_error',
                        sprintf("Error creating default response keys for service: %s", $service->name)
                    );
                }

                if (!$this->srResponseKeyService->validateSrResponseKeys($serviceRequest, true)) {
                    $this->addError(
                        'validator_error',
                        sprintf("Error validating response keys for service request: %s", $serviceRequest->name)
                    );
                }

                if (!$this->requestConfigService->requestConfigValidator($serviceRequest, true)) {
                    $this->addError(
                        'validator_error',
                        sprintf("Error validating request config for service request: %s", $serviceRequest->name)
                    );
                }
            }
        }
        return true;
    }
}
