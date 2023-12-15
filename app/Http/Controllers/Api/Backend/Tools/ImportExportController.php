<?php
namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\SecurityService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\IExport\ExportService;
use App\Services\Tools\IExport\ImportService;
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
 * @Route("/api/tools")
 */
class ImportExportController extends Controller
{
    private SecurityService $securityService;
    private UserAdminService $userService;
    private ExportService $exportService;

    /**
     * ExportController constructor.
     * Initialises services used in this controller
     *
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param SecurityService $securityService
     * @param UserAdminService $userService
     * @param AccessControlService $accessControlService
     */
    public function __construct(SerializerService $serializerService, HttpRequestService $httpRequestService,
                                SecurityService $securityService, UserAdminService $userService,
                                AccessControlService $accessControlService, ExportService $exportService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->securityService = $securityService;
        $this->userService = $userService;
        $this->exportService = $exportService;
    }

    /**
     * @Route("/export/list", name="api_export_list", methods={"GET"})
     * @param Request $request
     * @param ExportService $exportService
     * @return JsonResponse
     */
    public function getExportList(Request $request)
    {
        return $this->sendSuccessResponse("Export Response.",
            $this->exportService->getExportEntityListData($request->user())
        );
    }

    /**
     * @Route("/export", name="api_export", methods={"POST"})
     * @param Request $request
     * @param ExportService $exportService
     * @return JsonResponse
     */
    public function runExport(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $xmlDataArray = $this->exportService->getExportXmlDataArray($requestData);
        return $this->sendSuccessResponse("Export Response.", $this->exportService->storeXmlDataFromArray($xmlDataArray));
    }

    /**
     * @Route("/import", name="api_import", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function runImport(Request $request, ImportService $importService)
    {
        return $this->sendSuccessResponse("success", $importService->runImporter($request));
    }

    /**
     * @Route("/import/mappings", name="api_import_mappings", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function runImportMappings(Request $request, ImportService $importService)
    {
        return $this->sendSuccessResponse("success", $importService->runMappingsImporter($request));
    }

}
