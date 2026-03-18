<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\FileSystemService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 */
class FileSystemController extends Controller
{
    public function __construct(
        private FileSystemService $fileSystemService,
    ) {
        parent::__construct();
    }

    public function downloadFile(
        File $file,
        DownloadsFileSystemService $fileSystemService,
        Request $request
    ) {
        $getFileDownloadLink = $fileSystemService->getFileDownloadUrl($file, $request->ip(), $request->userAgent());
        if ($getFileDownloadLink === null) {
            return $this->sendErrorResponse('Error downloading file.');
        }

        return $this->sendSuccessResponse('File download success', [
            'url' => $getFileDownloadLink,
        ]);
    }

    public function getFiles(Request $request)
    {
        $getServices = $this->fileSystemService->findByParams(
            $request->get('sort', 'filename'),
            $request->get('order', 'asc'),
            $request->get('count', -1)
        );

        return $this->sendSuccessResponse(
            'success',
            $getServices
        );
    }

    public function getSingleFile(File $file)
    {
        return $this->sendSuccessResponse(
            'success',
            $file
        );
    }

    public function deleteFile(File $file, Request $request)
    {
        $delete = $this->fileSystemService->deleteFile($file);
        if (! $delete) {
            return $this->sendErrorResponse(
                'Error deleting file',
                $delete
            );
        }

        return $this->sendSuccessResponse(
            'File deleted.',
            $delete
        );
    }
}
