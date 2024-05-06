<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiSearchItemResource;
use App\Http\Resources\ApiSearchListResourceCollection;
use App\Http\Resources\ApiSearchResource;
use App\Http\Resources\ApiSearchResourceCollection;
use App\Services\ApiManager\Operations\DataHandler\ApiRequestDataHandler;
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
        HttpRequestService $httpRequestService,
        SerializerService $serializerService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
    }

    public function searchOperation(string $type, ApiRequestDataHandler $apiRequestDataHandler, Request $request)
    {
        $data = $request->query->all();
//        $appEnv = $this->getParameter("app.env");
//        if ($appEnv === "prod") {
//            $cacheKey = preg_replace('/[^a-zA-Z0-9\']/', '', $request->getRequestUri());
//            $cache = new FilesystemAdapter();
//            $operationData = $cache->get($cacheKey, function (ItemInterface $item) use ($requestOperation, $data, $name) {
//                $item->expiresAfter(10800);
//
//                $requestOperation->setProviderName($data['provider']);
//                $requestOperation->setApiRequestName($name);
//                return $requestOperation->runOperation($data);
//            });
//        }

        $apiRequestDataHandler->setUser($request->user());
        switch ($type) {
            case 'list':
                $results = $apiRequestDataHandler->runListSearch(
                    $request->query->get('provider'),
                    $request->query->get('service_request_name'),
                    $data
                );
                $responseData = new ApiSearchListResourceCollection($results);
                break;
            case 'item':
                $results = $apiRequestDataHandler->runItemSearch(
                    $request->query->get('provider'),
                    $request->query->get('service_request_name'),
                    $data['item_id']
                );
                $responseData = new ApiSearchItemResource($results);
                break;
            default:
                $this->sendErrorResponse(
                    'Invalid search type',
                );
        }
        return $this->sendSuccessResponse(
            'Success',
            $responseData
        );
    }
}
