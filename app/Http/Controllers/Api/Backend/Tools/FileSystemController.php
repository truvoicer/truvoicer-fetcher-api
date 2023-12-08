<?php
namespace App\Controller\Api\Backend\Tools;

use App\Controller\Api\BaseController;
use App\Entity\File;
use App\Service\Permission\AccessControlService;
use App\Service\Tools\FileSystem\Downloads\DownloadsFileSystemService;
use App\Service\Tools\HttpRequestService;
use App\Service\Tools\SerializerService;
use App\Service\Tools\FileSystem\FileSystemService;
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
 * @Route("/api/tools/filesystem")
 */
class FileSystemController extends BaseController
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
     * @Route("/{file}/download", name="api_generate_file_download", methods={"GET"})
     * @param File $file
     * @param DownloadsFileSystemService $fileSystemService
     * @return JsonResponse
     */
    public function downloadFile(File $file, DownloadsFileSystemService $fileSystemService) {
        $getFileDownloadLink = $fileSystemService->getFileDownloadUrl($file);
        if ($getFileDownloadLink === null) {
            return $this->jsonResponseFail("Error downloading file.". []);
        }
        return $this->jsonResponseSuccess("File download success", [
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
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray($getServices, ["list"]));
    }

    /**
     * @Route("/{file}", name="api_get_single_file", methods={"GET"})
     * @param File $file
     * @return JsonResponse
     */
    public function getSingleFile(File $file)
    {
        return $this->jsonResponseSuccess("success",
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
            return $this->jsonResponseFail("Error deleting file", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->jsonResponseSuccess("File deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}