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
        $data = $request->all();
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
        $provider = $request->get('provider');
        $service = $request->get('service');
        $apiRequestDataHandler->setUser($request->user());
        $results = $apiRequestDataHandler->searchOperation($type, $provider, $service, $data);
        if (!$results) {
            $this->sendErrorResponse(
                'Invalid search type',
            );
        }
        return $this->sendSuccessResponse(
            'Success',
            $results
        );
    }

}
