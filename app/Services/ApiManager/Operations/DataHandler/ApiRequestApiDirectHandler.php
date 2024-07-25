<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\User;

class ApiRequestApiDirectHandler
{
    private User $user;
    public function __construct()
    {
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
        return true;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
