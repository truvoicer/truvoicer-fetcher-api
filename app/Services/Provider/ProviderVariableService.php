<?php

namespace App\Services\Provider;

use App\Services\Variable\VariableService;
use Truvoicer\TfDbReadCore\Models\Provider;

class ProviderVariableService extends VariableService
{
    public function getVariableList(Provider $provider)
    {

        return [
            [
                'name' => 'generic',
                'label' => 'Generic',
                'variables' => $this->getReservedParameterKeys(),
            ],
            [
                'name' => 'properties',
                'label' => 'Properties',
                'variables' => $this->buildProviderProperties($provider),
            ],
        ];
    }

    public function buildProviderProperties(Provider $provider)
    {
        return $provider->properties
            ->pluck('name', 'name') // Get name as key, value as value
            ->map(fn ($name) => "[$name]") // Wrap each value in brackets
            ->toArray(); // Convert to array
    }
}
