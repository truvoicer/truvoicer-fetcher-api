<?php
namespace App\Controller\Api\Backend\Tools;

use App\Controller\Api\BaseController;
use App\Entity\File;
use App\Entity\Provider;
use App\Service\ApiManager\ApiBase;
use App\Service\Permission\AccessControlService;
use App\Service\Permission\PermissionService;
use App\Service\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Service\Tools\HttpRequestService;
use App\Service\Tools\SerializerService;
use App\Service\Tools\FileSystem\FileSystemService;
use App\Service\Tools\VariablesService;
use App\Service\UserService;
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
class UtilsController extends BaseController
{
    private FileSystemService $fileSystemService;
    private UserService $userService;

    /**
     * ExportController constructor.
     * Initialises services used in this controller
     *
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param FileSystemService $fileSystemService
     * @param UserService $userService
     * @param AccessControlService $accessControlService
     */
    public function __construct(SerializerService $serializerService, HttpRequestService $httpRequestService,
                                FileSystemService $fileSystemService, UserService $userService,
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
            return $this->jsonResponseFail("Missing type parameter", []);
        }
        $variableType = $request->query->get('type');

        return $this->jsonResponseSuccess(
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
        return $this->jsonResponseSuccess(
            "success",
            ApiBase::PAGINATION_TYPES
        );
    }
}
