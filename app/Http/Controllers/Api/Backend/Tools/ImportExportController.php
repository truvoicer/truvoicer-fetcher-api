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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $data = [];
        if ($request->query->getBoolean('include_data', false)) {
            $data = $this->exportService->getExportEntityListData($request->user());
        } else {
            $data = $this->exportService->getExportEntityFields($request->user());
        }
        return $this->sendSuccessResponse(
            "Export Response.",
            $data
        );
    }

    public function runExport(ExportRequest $request)
    {
        $this->exportService->setUser($request->user());
        $xmlDataArray = $this->exportService->getExportDataArray($request->validated());
        if (!count($xmlDataArray)) {
            return $this->sendErrorResponse(
                "Export Store error: No data to export."
            );
        }

        $fileName = sprintf("export-%s.json", (new \DateTime())->format("YmdHis"));
        $fileDirectory = sprintf("exports/%s", $fileName);

        $store = $this->exportService->storeXmlDataFromArray($xmlDataArray, $fileDirectory, $fileName);
        if (!$store) {
            return $this->sendErrorResponse(
                "Export Store error: Unable to store data."
            );
        }


        $getSavedData = $this->exportService->getDownloadsFileSystem()->saveDownloadsFileToDatabase(
            $fileDirectory,
            $fileName,
            "export",
            "json"
        );
        if (!$getSavedData) {
            return $this->sendErrorResponse(
                "Export save item error: Unable to save item to database."
            );
        }

        if (
            !$this->exportService->getDownloadsFileSystem()->fileSystemService->createFileDownload(
                $getSavedData, $request->getClientIp(), $request->userAgent()
            )
        ) {
            return $this->sendErrorResponse(
                'Error creating file download'
            );
        }
        return $this->sendSuccessResponse(
            "Export file saved: Type (json)",
            [
                "file_url" => $this->exportService->getDownloadsFileSystem()->buildDownloadUrl(
                    $this->exportService->getDownloadsFileSystem()->fileSystemService->getFileDownloadRepository()->getModel()
                )
            ]
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
