<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperationsRequest;
use App\Services\ApiManager\Operations\DataHandler\ApiRequestDataInterface;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class OperationsController extends Controller
{

    public function __construct(
    ) {
        parent::__construct();
    }

    public function searchOperation(string $type, ApiRequestDataInterface $apiRequestDataHandler, OperationsRequest $request)
    {
        $provider = $request->get('provider');
        $service = $request->get('service');
        $apiRequestDataHandler->setUser($request->user());

        $results = $apiRequestDataHandler->searchOperation(
            $request->validated('api_fetch_type'),
            $type,
            $provider,
            $service,
            $request->all()
        );
        if (!$results) {
            return $this->sendErrorResponse(
                'No results found',
            );
        }
        return $this->sendSuccessResponse(
            'Success',
            $results
        );
    }

}
