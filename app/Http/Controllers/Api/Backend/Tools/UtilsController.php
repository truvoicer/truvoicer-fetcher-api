<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\VariablesService;
use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 */
class UtilsController extends Controller
{
    public function __construct(
        private FileSystemService $fileSystemService,
        private UserAdminService $userService,
    ) {
        parent::__construct();
    }

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

        return $this->sendSuccessResponse(
            'success',
            $variablesService->getVariables($variableType)
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
