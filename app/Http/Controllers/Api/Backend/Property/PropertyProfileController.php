<?php

namespace App\Http\Controllers\Api\Backend\Property;

use App\Http\Controllers\Controller;
use App\Services\Property\PropertyProfilesService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for properties related tasks
 */
class PropertyProfileController extends Controller
{
    public function index(Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$this->accessControlService->inAdminGroup()) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }


        return $this->sendSuccessResponse(
            "success",
            PropertyProfilesService::PROFILES
        );
    }

}
