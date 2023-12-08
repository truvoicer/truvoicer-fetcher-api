<?php

namespace App\Controller\Api\Frontend;

use App\Controller\Api\BaseController;
use App\Service\ApiManager\Operations\RequestOperation;
use App\Service\Permission\AccessControlService;
use App\Service\Tools\HttpRequestService;
use App\Service\Tools\SerializerService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 */
class OperationsController extends BaseController
{

    public function __construct(HttpRequestService $httpRequestService,
                                SerializerService $serializerService,
                                AccessControlService $accessControlService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
    }

    /**
     * @Route("/api/operation/{service_request_name}", name="api_request_operation", methods={"GET"})
     * @param string $service_request_name
     * @param RequestOperation $requestOperation
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOperation(string $service_request_name, RequestOperation $requestOperation, Request $request)
    {
        $data = $request->query->all();
        $appEnv = $this->getParameter("app.env");
        if ($appEnv === "prod") {
            $cacheKey = preg_replace('/[^a-zA-Z0-9\']/', '', $request->getRequestUri());
            $cache = new FilesystemAdapter();
            $operationData = $cache->get($cacheKey, function (ItemInterface $item) use ($requestOperation, $data, $service_request_name) {
                $item->expiresAfter(10800);

                $requestOperation->setProviderName($data['provider']);
                $requestOperation->setApiRequestName($service_request_name);
                return $requestOperation->runOperation($data);
            });
        } else {
            $requestOperation->setProviderName($data['provider']);
            $requestOperation->setApiRequestName($service_request_name);
            $operationData = $requestOperation->runOperation($data);
        }
        return new JsonResponse(
            is_array($operationData) ?
                $this->serializerService->entityArrayToArray($operationData)
                :
                $this->serializerService->entityToArray($operationData),
            Response::HTTP_OK);
    }
}
