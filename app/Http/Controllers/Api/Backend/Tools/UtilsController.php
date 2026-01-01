<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use Truvoicer\TruFetcherGet\Services\ApiManager\ApiBase;
use Truvoicer\TruFetcherGet\Services\ApiManager\Data\DataConstants;
use Truvoicer\TruFetcherGet\Services\Permission\AccessControlService;
use Truvoicer\TruFetcherGet\Services\Permission\PermissionService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\VariablesService;
use Truvoicer\TruFetcherGet\Services\User\UserAdminService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class UtilsController extends Controller
{

    public function __construct(
        private FileSystemService $fileSystemService,
        private  UserAdminService $userService,
    ) {
        parent::__construct();
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     *
     */
    public function getVariableList(Request $request, VariablesService $variablesService)
    {
        if (!$request->query->has('type')) {
            return $this->sendErrorResponse("Missing type parameter", []);
        }
        $variableType = $request->query->get('type');

        return $this->sendSuccessResponse(
            "success",
            $variablesService->getVariables($variableType)
        );
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     *
     */
    public function getPaginationTypes()
    {
        return $this->sendSuccessResponse(
            "success",
            DataConstants::PAGINATION_TYPES
        );
    }
}
