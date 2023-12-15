<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Http\Controllers\Controller;
use App\Entity\Property;
use App\Entity\Provider;
use App\Repository\ProviderRepository;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for provider related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/provider")
 */
class ProviderController extends Controller
{
    const DEFAULT_ENTITY = "provider";

    private ProviderRepository $providerRepo;
    private ProviderService $providerService;

    /**
     * ProviderController constructor.
     * @param ProviderRepository $providerRepository
     * @param ProviderService $providerService
     * @param AccessControlService $accessControlService
     * @param HttpRequestService $httpRequestService
     * @param SerializerService $serializerService
     */
    public function __construct(ProviderRepository $providerRepository,
                                HttpRequestService $httpRequestService,
                                SerializerService $serializerService,
                                ProviderService $providerService,
                                AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerRepo = $providerRepository;
        $this->providerService = $providerService;
    }

    /**
     * Gets a list of providers from the database based on the get request query parameters
     *
     * @Route("/list", name="api_get_providers", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProviderList(Request $request)
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN')) {
            $providers = $this->providerService->getProviderList(
                $request->get('sort', "provider_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );
        } else {
            $providers = $this->providerService->findUserPermittedProviders(
                $request->get('sort', "provider_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null),
                $request->user()
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray(
                $providers,
                ["list"]));
    }

    /**
     * Gets a single provider from the database based on the id in the get request url
     *
     * @Route("/{provider}", name="api_get_provider", methods={"GET"})
     * @param Provider $provider
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProvider(Provider $provider)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($provider, ["single"]));
    }

    /**
     * Creates a provider in the database based on the post request data
     *
     * @param Request $request
     * @Route("/create", name="api_create_provider", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createProvider(Request $request)
    {
        $createProvider = $this->providerService->createProvider(
            $this->httpRequestService->getRequestData($request, true));

        if (!$createProvider) {
            return $this->sendErrorResponse("Error inserting provider");
        }
        return $this->sendSuccessResponse("Provider added",
            $this->serializerService->entityToArray($createProvider, ['main']));
    }


    /**
     * Updates a provider in the database based on the post request data
     *
     * @param Request $request
     * @Route("/{provider}/update", name="api_update_provider", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateProvider(Provider $provider, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $updateProvider = $this->providerService->updateProvider(
            $provider,
            $this->httpRequestService->getRequestData($request, true));

        if (!$updateProvider) {
            return $this->sendErrorResponse("Error updating provider");
        }
        return $this->sendSuccessResponse("Provider updated",
            $this->serializerService->entityToArray($updateProvider, ['main']));
    }


    /**
     * Deletes a provider in the database based on the post request data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/{provider}/delete", name="api_delete_provider", methods={"POST"})
     */
    public function deleteProvider(Provider $provider, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            );
        }
        $delete = $this->providerService->deleteProvider($provider);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting provider", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->sendSuccessResponse("Provider deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
