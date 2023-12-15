<?php
namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Entity\File;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\FileSystem\FileSystemService;
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
 * @Route("/api/tools/filesystem")
 */
class FileSystemController extends Controller
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
     * @Route("/{file}/download", name="api_generate_file_download", methods={"GET"})
     * @param File $file
     * @param DownloadsFileSystemService $fileSystemService
     * @return JsonResponse
     */
    public function downloadFile(File $file, DownloadsFileSystemService $fileSystemService) {
        $getFileDownloadLink = $fileSystemService->getFileDownloadUrl($file);
        if ($getFileDownloadLink === null) {
            return $this->sendErrorResponse("Error downloading file.". []);
        }
        return $this->sendSuccessResponse("File download success", [
            "url" => $getFileDownloadLink
        ]);
    }

    /**
     * @Route("/list", name="api_get_files", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getFiles(Request $request)
    {
        $getServices = $this->fileSystemService->findByParams(
            $request->get('sort', "filename"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($getServices, ["list"]));
    }

    /**
     * @Route("/{file}", name="api_get_single_file", methods={"GET"})
     * @param File $file
     * @return JsonResponse
     */
    public function getSingleFile(File $file)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($file, ["single"]));
    }

    /**
     * @param Request $request
     * @Route("/{file}/delete", name="api_delete_file", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteFile(File $file, Request $request)
    {
        $delete = $this->fileSystemService->deleteFile($file);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting file", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->sendSuccessResponse("File deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}
