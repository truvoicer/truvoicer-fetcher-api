<?php

namespace App\Services\Tools;

use App\Enums\Variable\VariableType;
use App\Services\ApiServices\ServiceRequests\SrVariableService;
use App\Services\Provider\ProviderVariableService;

class VariablesService
{

    public function getVariables(VariableType $variableType)
    {
        return match ($variableType) {
            VariableType::SR => app(SrVariableService::class),
            VariableType::PROVIDER => app(ProviderVariableService::class),
            default => [],
        };
    }

}
