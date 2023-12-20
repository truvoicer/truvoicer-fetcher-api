<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ApiManager\Operations\RequestOperation;
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
        AccessControlService $accessControlService,
        Request $request
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
    }

    public function searchOperation(string $service_request_name, RequestOperation $requestOperation, Request $request)
    {
        $data = $request->query->all();
//        $appEnv = $this->getParameter("app.env");
//        if ($appEnv === "prod") {
//            $cacheKey = preg_replace('/[^a-zA-Z0-9\']/', '', $request->getRequestUri());
//            $cache = new FilesystemAdapter();
//            $operationData = $cache->get($cacheKey, function (ItemInterface $item) use ($requestOperation, $data, $service_request_name) {
//                $item->expiresAfter(10800);
//
//                $requestOperation->setProviderName($data['provider']);
//                $requestOperation->setApiRequestName($service_request_name);
//                return $requestOperation->runOperation($data);
//            });
//        } else {
//            $requestOperation->setProviderName($data['provider']);
//            $requestOperation->setApiRequestName($service_request_name);
//            $operationData = $requestOperation->runOperation($data);
//        }
//        return new JsonResponse(
//            is_array($operationData) ?
//                $this->serializerService->entityArrayToArray($operationData)
//                :
//                $this->serializerService->entityToArray($operationData),
//            Response::HTTP_OK);
        return $this->sendSuccessResponse('Success');
    }
}
