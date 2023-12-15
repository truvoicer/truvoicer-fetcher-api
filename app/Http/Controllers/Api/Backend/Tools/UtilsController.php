<?php
namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Entity\File;
use App\Entity\Provider;
use App\Services\ApiManager\ApiBase;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\VariablesService;
use App\Services\User\UserAdminService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/tools/utils")
 */
class UtilsController extends Controller
{
    private FileSystemService $fileSystemService;
    private UserAdminService $userService;

    /**
     * ExportController constructor.
     * Initialises services used in this controller
     *
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param FileSystemService $fileSystemService
     * @param UserAdminService $userService
     * @param AccessControlService $accessControlService
     */
    public function __construct(SerializerService $serializerService, HttpRequestService $httpRequestService,
                                FileSystemService $fileSystemService, UserAdminService $userService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->fileSystemService = $fileSystemService;
        $this->userService = $userService;
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     *
     * @Route("/variable/list", name="api_get_service_request_variable_list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
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
     * @Route("/pagination-types", name="api_get_pagination_types_list", methods={"GET"})
     * @return JsonResponse
     */
    public function getPaginationTypes()
    {
        return $this->sendSuccessResponse(
            "success",
            ApiBase::PAGINATION_TYPES
        );
    }
}
