<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Models\File;
use Truvoicer\TruFetcherGet\Services\Permission\AccessControlService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\FileSystem\FileSystemService;
use Truvoicer\TruFetcherGet\Services\User\UserAdminService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class FileSystemController extends Controller
{

    public function __construct(
        private FileSystemService $fileSystemService,
        private UserAdminService $userService,
    ) {
        parent::__construct();
    }

    public function downloadFile(File $file, DownloadsFileSystemService $fileSystemService)
    {
        $getFileDownloadLink = $fileSystemService->getFileDownloadUrl($file);
        if ($getFileDownloadLink === null) {
            return $this->sendErrorResponse("Error downloading file." . []);
        }
        return $this->sendSuccessResponse("File download success", [
            "url" => $getFileDownloadLink
        ]);
    }

    public function getFiles(Request $request)
    {
        $getServices = $this->fileSystemService->findByParams(
            $request->get('sort', "filename"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray($getServices, ["list"])
        );
    }

    public function getSingleFile(File $file)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($file, ["single"])
        );
    }

    public function deleteFile(File $file, Request $request)
    {
        $delete = $this->fileSystemService->deleteFile($file);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting file",
                $this->serializerService->entityToArray($delete, ['single'])
            );
        }
        return $this->sendSuccessResponse(
            "File deleted.",
            $this->serializerService->entityToArray($delete, ['single'])
        );
    }
}
