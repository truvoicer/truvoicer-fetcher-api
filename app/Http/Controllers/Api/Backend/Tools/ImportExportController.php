<?php
namespace App\Controller\Api\Backend\Tools;

use App\Controller\Api\BaseController;
use App\Service\Permission\AccessControlService;
use App\Service\Tools\HttpRequestService;
use App\Service\SecurityService;
use App\Service\Tools\SerializerService;
use App\Service\Tools\IExport\ExportService;
use App\Service\Tools\IExport\ImportService;
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
 * @Route("/api/tools")
 */
class ImportExportController extends BaseController
{
    private SecurityService $securityService;
    private UserService $userService;
    private ExportService $exportService;

    /**
     * ExportController constructor.
     * Initialises services used in this controller
     *
     * @param SerializerService $serializerService
     * @param HttpRequestService $httpRequestService
     * @param SecurityService $securityService
     * @param UserService $userService
     * @param AccessControlService $accessControlService
     */
    public function __construct(SerializerService $serializerService, HttpRequestService $httpRequestService,
                                SecurityService $securityService, UserService $userService,
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
        return $this->jsonResponseSuccess("Export Response.",
            $this->exportService->getExportEntityListData($this->getUser())
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
        return $this->jsonResponseSuccess("Export Response.", $this->exportService->storeXmlDataFromArray($xmlDataArray));
    }

    /**
     * @Route("/import", name="api_import", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function runImport(Request $request, ImportService $importService)
    {
        return $this->jsonResponseSuccess("success", $importService->runImporter($request));
    }

    /**
     * @Route("/import/mappings", name="api_import_mappings", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function runImportMappings(Request $request, ImportService $importService)
    {
        return $this->jsonResponseSuccess("success", $importService->runMappingsImporter($request));
    }

}
