<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntityRequest;
use Truvoicer\TfDbReadCore\Services\ApiManager\ApiBase;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\EntityService;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use Truvoicer\TfDbReadCore\Services\Permission\PermissionService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\VariablesService;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class EntityController extends Controller
{

    public function __construct(
        private EntityService $entityService
    ) {
        parent::__construct();
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     *
     */
    public function index(EntityRequest $request)
    {


        return $this->sendSuccessResponse(
            "success",
            $this->entityService->getEntityList(
                $request->user(),
                $request->validated('entity'),
                $request->validated('ids')
            )
        );
    }

}
