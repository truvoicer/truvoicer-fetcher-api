<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Repositories\PropertyRepository;
use App\Services\Permission\AccessControlService;
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
        AccessControlService $accessControlService,
        Request $request
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
        $this->propertyRepository = $propertyRepository;
        $this->propertyService = $propertyService;
    }

    public function getPropertyList(Request $request)
    {
        $properties = [];
        if ($this->accessControlService->inAdminGroup()) {
            $properties = $this->serializerService->entityArrayToArray(
                $this->propertyService->findPropertiesByParams(
                    $request->get('sort', "property_name"),
                    $request->get('order', "asc"),
                    (int)$request->get('count', null)
                )
            );
        }
        return $this->sendSuccessResponse(
            "success",
            $properties
        );
    }

    public function getProperty(Property $property)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityToArray($property, ["main"])
        );
    }

    public function updateProperty(Property $property, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $updateProperty = $this->propertyService->updateProperty($property, $requestData);

        if (!$updateProperty) {
            return $this->sendErrorResponse("Error updating property");
        }
        return $this->sendSuccessResponse(
            "Property updated",
            $this->serializerService->entityToArray($updateProperty, ['main'])
        );
    }

    public function createProperty(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);

        $createProperty = $this->propertyService->createProperty($requestData);
        if (!$createProperty) {
            return $this->sendErrorResponse("Error creating property");
        }
        return $this->sendSuccessResponse(
            "Property created",
            $this->serializerService->entityToArray($createProperty, ['main'])
        );
    }

    public function deleteProperty(Property $property, Request $request)
    {
        $delete = $this->propertyService->deleteProperty($property);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting property",
                $this->serializerService->entityToArray($delete, ['main'])
            );
        }
        return $this->sendSuccessResponse(
            "Property deleted.",
            $this->serializerService->entityToArray($delete, ['main'])
        );
    }
}
