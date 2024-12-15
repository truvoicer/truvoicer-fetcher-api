<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Events\ImportMappingsEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Export\ExportRequest;
use App\Http\Requests\Admin\Tools\Import\ImportMappingsRequest;
use App\Http\Requests\Admin\Tools\Import\ParseImportRequest;
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
    /**
     * ExportController constructor.
     * Initialises services used in this controller
     *
     * @param AccessControlService $accessControlService
     * @param ExportService $exportService
     * @param ImportService $importService
     */
    public function __construct(
        protected AccessControlService $accessControlService,
        protected ExportService $exportService,
        protected ImportService $importService
    ) {
        parent::__construct($accessControlService);
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

    public function parseImport(ParseImportRequest $request, ImportService $importService)
    {
        $importService->setUser($request->user());
        return $this->sendSuccessResponse(
            "success",
            $importService->parseImport(
                $request->files->get("upload_file")
            ),
            $importService->getErrors()
        );
    }

    public function runImportMappings(ImportMappingsRequest $request)
    {
        ImportMappingsEvent::dispatch(
            $request->user()->id,
            $request->validated('file_id'),
            $request->validated('mappings')
        );
        return $this->sendSuccessResponse(
            "Import mappings event dispatched."
        );
    }

}
