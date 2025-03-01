<?php

namespace App\Http\Controllers\Api\Backend\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\CreatePropertyRequest;
use App\Http\Requests\Property\DeleteBatchPropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyCollection;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Repositories\PropertyRepository;
use App\Services\Property\PropertyService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for properties related tasks
 */
class PropertyController extends Controller
{

    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyService $propertyService,
    ) {
        parent::__construct();
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
