<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\FileDownload;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\FileSystem\FileSystemService;
use App\Services\Tools\FileSystem\Uploads\UploadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DownloadFileController extends Controller
{

    public function __construct(
        protected AccessControlService     $accessControlService,
        protected HttpRequestService       $httpRequestService,
        protected SerializerService        $serializerService,
        private DownloadsFileSystemService $downloadsFileSystemService,
        private UploadsFileSystemService   $uploadsFileSystemService,
    )
    {
        parent::__construct($accessControlService);
    }

    /**
     * @param FileDownload $fileDownload
     * @param FileSystemService $fileSystemService
     */
    public function __invoke(FileDownload $fileDownload, FileSystemService $fileSystemService, Request $request): StreamedResponse
    {
        if (!$fileDownload->exists) {
            throw new BadRequestHttpException("File download not found.");
        }

        $file = $fileDownload->file()->first();
        if (!$file) {
            throw new BadRequestHttpException("File not found.");
        }
        if ($fileDownload->client_ip !== $request->getClientIp()) {
            throw new BadRequestHttpException("Client IP does not match.");
        }
        if ($fileDownload->user_agent !== $request->userAgent()) {
            throw new BadRequestHttpException("User agent does not match.");
        }
        $contentDisposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $file->filename,
            $file->filename
        );

        $response = response()->streamDownload(function () use ($file) {
            $outputStream = fopen('php://output', 'wb');
            $stream = $this->getFileStream($file->file_system, $file->rel_path);
            stream_copy_to_stream($stream, $outputStream);
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', $file->mime_type);
        $response->headers->set('Content-Disposition', $contentDisposition);

        $fileSystemService->deleteFileDownload($fileDownload);
        return $response;
    }

    public function getFileStream(string $fileSystem, string $path)
    {
        return match ($fileSystem) {
            DownloadsFileSystemService::FILE_SYSTEM_NAME => $this->downloadsFileSystemService->readFileStream($path),
            UploadsFileSystemService::FILE_SYSTEM_NAME => $this->uploadsFileSystemService->readFileStream($path),
            default => throw new BadRequestHttpException(sprintf("File system not recognised: (%s)", $fileSystem)),
        };
    }
}
