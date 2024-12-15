<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\CreatePropertyRequest;
use App\Http\Requests\Property\DeleteBatchPropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyCollection;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Repositories\PropertyRepository;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Property\PropertyService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for properties related tasks
 */
class PropertyController extends Controller
{
    private PropertyRepository $propertyRepository;
    private PropertyService $propertyService;

    /**
     * PropertyController constructor.
     * Initialises services to be used in this class
     *
     * @param PropertyRepository $propertyRepository
     * @param HttpRequestService $httpRequestService
     * @param PropertyService $propertyService
     * @param SerializerService $serializerService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        PropertyRepository $propertyRepository,
        HttpRequestService $httpRequestService,
        PropertyService $propertyService,
        SerializerService $serializerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService);
        $this->propertyRepository = $propertyRepository;
        $this->propertyService = $propertyService;
    }

    public function getPropertyList(Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }

        $this->propertyRepository->setOrderDir($request->get('order', "asc"));
        $this->propertyRepository->setSortField($request->get('sort', "name"));
        $this->propertyRepository->setLimit($request->get('count', -1));
        return $this->sendSuccessResponse(
            "success",
            new PropertyCollection(
                $this->propertyRepository->findMany()
            )
        );
    }

    public function getProperty(Property $property, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        return $this->sendSuccessResponse(
            "success",
            new PropertyResource($property)
        );
    }

    public function updateProperty(Property $property, UpdatePropertyRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        $updateProperty = $this->propertyService->updateProperty($property, $request->validated());

        if (!$updateProperty) {
            return $this->sendErrorResponse("Error updating property");
        }
        return $this->sendSuccessResponse(
            "Property updated",
            new PropertyResource(
                $this->propertyService->getPropertyRepository()->getModel()
            )
        );
    }

    public function createProperty(CreatePropertyRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        $createProperty = $this->propertyService->createProperty($request->validated());
        if (!$createProperty) {
            return $this->sendErrorResponse("Error creating property");
        }
        return $this->sendSuccessResponse(
            "Property created",
            new PropertyResource(
                $this->propertyService->getPropertyRepository()->getModel()
            )
        );
    }

    public function deleteProperty(Property $property, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        $delete = $this->propertyService->deleteProperty($property);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting property"
            );
        }
        return $this->sendSuccessResponse(
            "Property deleted."
        );
    }
    public function deleteBatch(
        DeleteBatchPropertyRequest $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());

        if (!$this->propertyService->deleteBatch($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting properties",
            );
        }
        return $this->sendSuccessResponse(
            "Properties deleted.",
        );
    }
}
