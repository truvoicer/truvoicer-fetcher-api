<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\User;

class ApiRequestDataInterface
{
    private User $user;

    public function __construct(
        private ApiRequestMongoDbHandler $apiRequestMongoDbHandler,
        private ApiRequestApiDirectHandler $apiRequestApiDirectHandler
    )
    {
    }

    public function searchOperation(
        string $fetchType,
        string $serviceType,
        array $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        if (!count($providers)) {
            return false;
        }

        $this->apiRequestMongoDbHandler->setUser($this->user);
        $this->apiRequestApiDirectHandler->setUser($this->user);

        return match ($fetchType) {
            'database' => $this->apiRequestMongoDbHandler->searchOperation(
                $serviceType, $providers, $serviceName, $data
            ),
            'api_direct' => $this->apiRequestApiDirectHandler->searchOperation(
                $serviceType, $providers, $serviceName, $data
            ),
            default => false,
        };
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
