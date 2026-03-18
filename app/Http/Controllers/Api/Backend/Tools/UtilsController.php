<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Enums\Variable\VariableType;
use App\Http\Controllers\Controller;
use App\Services\Tools\VariablesService;
use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 */
class UtilsController extends Controller
{
    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     */
    public function getVariableList(Request $request, VariablesService $variablesService)
    {
        if (! $request->query->has('type')) {
            return $this->sendErrorResponse('Missing type parameter', []);
        }
        $variableType = $request->query->get('type');
        $variableTypeEnum = VariableType::tryFrom($variableType);
        if (! $variableTypeEnum) {
            return $this->sendErrorResponse('Variable type not supported. | variable type: '.$variableType);
        }

        return $this->sendSuccessResponse(
            'success',
            $variablesService->getVariables($variableTypeEnum)
        );
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     */
    public function getPaginationTypes()
    {
        return $this->sendSuccessResponse(
            'success',
            DataConstants::PAGINATION_TYPES
        );
    }
}
