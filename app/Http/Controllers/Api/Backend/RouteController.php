<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RouteResource;
use App\Services\Permission\AccessControlService;
use App\Services\RouteService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class RouteController extends Controller
{
    public function __construct(
        AccessControlService $accessControlService,
        HttpRequestService $httpRequestService,
        SerializerService $serializerService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
    }

    public function getRouteList(Request $request)
    {
        $this->accessControlService->setUser($request->user());
        dd(RouteService::getRoutes('backend.'));
        return $this->sendSuccessResponse(
            "success",
            RouteResource::collection(
                Route::getRoutes()->getRoutes()
            )
        );
    }
}
