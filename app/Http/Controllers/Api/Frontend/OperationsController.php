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
        ini_set('max_execution_time', 60);
        $provider = $request->validated('provider', []);
        $service = $request->validated('service');
        $apiRequestDataHandler->setUser($request->user())
            ->setRequestData($request->all());

        $results = $apiRequestDataHandler->searchOperation(
            $request->validated('api_fetch_type'),
            $type,
            $provider,
            $service
        );
        if (!$results) {
            return $this->sendErrorResponse(
                'No results found',
            );
        }

        ini_restore('max_execution_time');
        return $results;
    }

}
