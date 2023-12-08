<?php

namespace App\Controller\Api\Backend\Provider;

use App\Controller\Api\BaseController;
use App\Entity\Property;
use App\Entity\Provider;
use App\Repository\ProviderRepository;
use App\Service\Permission\AccessControlService;
use App\Service\Permission\PermissionService;
use App\Service\Tools\HttpRequestService;
use App\Service\Provider\ProviderService;
use App\Service\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for provider related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/provider/{provider}/property")
 */
class ProviderPropertyController extends BaseController
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
     * Gets a list of related provider property objects based on the get request
     * query parameters
     *
     * @Route("/list", name="api_get_provider_property_list", methods={"GET"})
     * @param Provider $provider
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProviderPropertyList(Provider $provider, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $this->getUser(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        $getProviderProps = $this->providerService->getProviderProperties(
            $provider->getId(),
            $request->get('sort', "property_name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->jsonResponseSuccess("success", $getProviderProps);
    }

    /**
     * Gets a single related provider property based on
     * the provider id and property id in the url
     *
     * @Route("/{property}", name="api_get_provider_property", methods={"GET"})
     * @param Provider $provider
     * @param Property $property
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProviderProperty(Provider $provider, Property $property)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $this->getUser(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->jsonResponseSuccess("success",
            $this->providerService->getProviderPropertyObjectById($provider, $property)
        );
    }

    /**
     * Creates a related provider property in the database based on the post request data
     * Required data request data fields:
     * - provider_id
     * - property_id
     *
     * @param Provider $provider
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/create", name="api_create_provider_property", methods={"POST"})
     */
    public function createProviderProperty(Provider $provider, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $this->getUser(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_WRITE,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $requestData = $this->httpRequestService->getRequestData($request);
        $create = $this->providerService->createProviderProperty($provider, $requestData->data);
        if (!$create) {
            return $this->jsonResponseFail("Error adding provider property.");
        }
        return $this->jsonResponseSuccess("Successfully added provider property.",
            $this->serializerService->entityToArray($create));
    }

    /**
     * Updates a related provider property in the database based on the post request data
     * Required data request data fields:
     * - provider_id
     * - property_id
     *
     * @param Provider $provider
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/{property}/update", name="api_update_provider_property_relation", methods={"POST"})
     */
    public function updateProviderProperty(Provider $provider, Property $property, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $this->getUser(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $data = $this->httpRequestService->getRequestData($request, true);
        $update = $this->providerService->updateProviderProperty($provider, $property, $data);

        if (!$update) {
            return $this->jsonResponseFail("Error updating provider property");
        }
        return $this->jsonResponseSuccess("Provider property updated",
            $this->serializerService->entityToArray($update));
    }

    /**
     * Deletes a related provider property in the database based on the post request data
     * Required data request data fields:
     * - item_id (property_id)
     * - extra->provider_id
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/{property}/delete", name="api_delete_provider_property", methods={"POST"})
     */
    public function deleteProviderProperty(Provider $provider, Property $property, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $provider, $this->getUser(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            );
        }
        $delete = $this->providerService->deleteProviderProperty($provider, $property);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting provider property value", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->jsonResponseSuccess("Provider property value deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
