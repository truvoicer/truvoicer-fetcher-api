<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
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

    public function searchOperation(string $name, ApiRequestDataHandler $apiRequestDataHandler, Request $request)
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

        return $this->sendSuccessResponse(
            'Success',
            $apiRequestDataHandler->runSearch(
                $request->query->get('provider'),
                $name,
                $data
            )
        );
    }
}
