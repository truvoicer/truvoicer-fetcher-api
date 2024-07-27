<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\User;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestApiDirectHandler extends ApiRequestDataHandler
{
    private User $user;
    public function __construct(
        private readonly ApiRequestService $apiRequestService,
        private readonly EloquentCollection $srs,
        private readonly ProviderService    $providerService
    )
    {
        parent::__construct($srs, $providerService);
    }

    public function searchOperation(
        string $serviceType,
        array $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        if (!count($providers)) {
            return false;
        }
        $this->buildServiceRequests($providers, $serviceType);
        if ($this->srs->count() === 0) {
            throw new BadRequestHttpException("Providers not found");
        }

        $requestData = [];
        foreach ($this->srs as $index => $sr) {
            $provider = $sr->provider()->first();
            if (!$provider instanceof Provider) {
                return false;
            }

            $this->apiRequestService->setProvider($provider);
            if ($this->user->cannot('view', $provider)) {
                return false;
            }
            $this->apiRequestService->setSr($sr);
            if ($this->srs->count() === 1 && $index === 0) {
                return $this->apiRequestService->runOperation($data);
            }
            $apiResponse = $this->apiRequestService->runOperation($data);

            if ($apiResponse->getStatus() !== 'success') {
                continue;
            }
            $requestData = [
                ...$requestData,
                $apiResponse->getRequestData()
            ];
        }
        return $requestData;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->apiRequestService->setUser($user);
    }
}
