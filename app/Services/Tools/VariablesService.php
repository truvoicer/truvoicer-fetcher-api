<?php

namespace App\Services\Tools;

use App\Services\ApiManager\ApiBase;

class VariablesService
{

    public function getVariables(string $type)
    {
        return match ($type) {
            'service_request' => array_map(function ($item) {
                return $item['placeholder'];
            }, ApiBase::PARAM_FILTER_KEYS),
            default => [],
        };
    }

}
