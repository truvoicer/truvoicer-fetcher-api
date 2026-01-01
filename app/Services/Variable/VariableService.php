<?php
namespace App\Services\Variable;

use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;

class VariableService {

    protected function getReservedParameterKeys()
    {
        return array_map(function ($item) {
            return $item['placeholder'];
        }, DataConstants::PARAM_FILTER_KEYS);
    }
}
