<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Export\ExportRequest;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\SecurityService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\IExport\ExportService;
use App\Services\Tools\IExport\ImportService;
use App\Services\User\UserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
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
    public function __construct(
        SerializerService $serializerService,
        HttpRequestService $httpRequestService,
        SecurityService $securityService,
        UserAdminService $userService,
        AccessControlService $accessControlService,
        ExportService $exportService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->securityService = $securityService;
        $this->userService = $userService;
        $this->exportService = $exportService;
    }

    public function getExportList(Request $request)
    {
        return $this->sendSuccessResponse(
            "Export Response.",
            $this->exportService->getExportEntityListData($request->user())
        );
    }

    public function runExport(ExportRequest $request)
    {
        $this->exportService->setUser($request->user());
        $xmlDataArray = $this->exportService->getExportDataArray($request->validated());
        return $this->sendSuccessResponse(
            "Export Response.",
            $this->exportService->storeXmlDataFromArray($xmlDataArray)
        );
    }

    public function runImport(Request $request, ImportService $importService)
    {
        return $this->sendSuccessResponse("success", $importService->runImporter($request));
    }

    public function runImportMappings(Request $request, ImportService $importService)
    {
        return $this->sendSuccessResponse("success", $importService->runMappingsImporter($request));
    }

}
