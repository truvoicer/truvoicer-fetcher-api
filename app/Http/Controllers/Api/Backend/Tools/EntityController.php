<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntityRequest;
use Illuminate\Http\Request;
use Truvoicer\TfDbReadCore\Services\EntityService;

/**
 * Contains api endpoint functions for exporting tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 */
class EntityController extends Controller
{
    public function __construct(
        private EntityService $entityService
    ) {
        parent::__construct();
    }

    /**
     * Get list of service requests variables
     * Returns a list of service requests variables based on the request query parameters
     */
    public function index(EntityRequest $request)
    {

        return $this->sendSuccessResponse(
            'success',
            $this->entityService->getEntityList(
                $request->user(),
                $request->validated('entity'),
                $request->validated('ids')
            )
        );
    }
}
