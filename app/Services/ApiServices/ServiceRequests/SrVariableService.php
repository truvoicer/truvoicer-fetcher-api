<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Provider;
use App\Models\Sr;
use App\Services\Variable\VariableService;

class SrVariableService extends VariableService
{

    public function getVariableList(Provider $provider, Sr $sr)
    {

        return [
            [
                'name' => 'generic',
                'label' => 'Generic',
                'variables' => $this->getReservedParameterKeys()
            ],
            [
                'name' => 'sr_parameters',
                'label' => 'Sr Parameters',
                'variables' => $this->buildSrParameters($provider, $sr)
            ],
        ];
    }

    public function buildSrParameters(Provider $provider, Sr $sr)
    {
        return $sr->srParameter
            ->pluck('name', 'name') // Get name as key, value as value
            ->map(fn($name) => "[$name]") // Wrap each value in brackets
            ->toArray(); // Convert to array
    }
}
