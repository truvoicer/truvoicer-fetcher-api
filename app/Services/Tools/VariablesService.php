<?php

namespace App\Services\Tools;

use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Data\DataConstants;

class VariablesService
{

    public function getVariables(string $type)
    {
        return match ($type) {
            'service_request' => array_map(function ($item) {
                return $item['placeholder'];
            }, DataConstants::PARAM_FILTER_KEYS),
            default => [],
        };
    }

}
