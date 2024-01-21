<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\S;
use App\Models\Provider;
use App\Models\Sr;
use App\Repositories\CategoryRepository;
use App\Repositories\SRepository;
use App\Repositories\SrConfigRepository;
use App\Repositories\SrParameterRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SResponseKeyRepository;
use App\Services\ApiServices\ApiService;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Helpers\Tools\UtilHelpers;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestService extends BaseService
{
    private HttpRequestService $httpRequestService;
    private ProviderService $providerService;
    private SRepository $serviceRepository;
    private SrRepository $serviceRequestRepository;
    private SrParameterRepository $requestParametersRepo;
    private SrConfigRepository $requestConfigRepo;
    private SResponseKeyRepository $responseKeysRepo;
    private RequestConfigService $requestConfigService;
    private RequestParametersService $requestParametersService;
    private ApiService $apiService;

    public function __construct(HttpRequestService $httpRequestService,
                                ProviderService $providerService, RequestConfigService $requestConfigService,
                                RequestParametersService $requestParametersService, ApiService $apiService
    )
    {
        parent::__construct();
        $this->httpRequestService = $httpRequestService;
        $this->providerService = $providerService;
        $this->apiService = $apiService;
        $this->requestConfigService = $requestConfigService;
        $this->requestParametersService = $requestParametersService;
        $this->serviceRepository = new SRepository();
        $this->serviceRequestRepository = new SrRepository();
        $this->requestParametersRepo = new SrParameterRepository();
        $this->responseKeysRepo = new SResponseKeyRepository();
        $this->requestConfigRepo = new SrConfigRepository();
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRequestRepository->findByQuery($query);
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->serviceRequestRepository->setOrderDir($order);
        $this->serviceRequestRepository->setSortField($sort);
        $this->serviceRequestRepository->setLimit($count);
        return $this->serviceRequestRepository->findMany();
    }

    public function getRequestByName(Provider $provider, string $serviceRequestName = null)
    {
        return $this->serviceRequestRepository->getRequestByName($provider, $serviceRequestName);
    }

    public function getRequestByRequestName(Provider $provider, string $serviceName = null)
    {
        return $this->serviceRequestRepository->getRequestByName($provider, $serviceName);
    }

    public function getServiceByRequestName(Provider $provider, string $serviceName = null)
    {
        return $this->serviceRepository->getServiceByRequestName($provider, $serviceName);
    }

    public function getServiceRequestById($id)
    {
        $getServiceRequest = $this->serviceRequestRepository->findById($id);
        if ($getServiceRequest === null) {
            throw new BadRequestHttpException("Service request does not exist in database.");
        }
        return $this->castServiceRequest($getServiceRequest);
    }

    public function castServiceRequest(Sr $serviceRequest)
    {
        return $serviceRequest;
    }

    public function getUserServiceRequestByProvider(Provider $provider, string $sort, string $order, ?int $count = null)
    {
        return $this->serviceRequestRepository->getServiceRequestByProvider(
            $provider,
            $sort,
            $order,
            $count
        );
    }

    public function getProviderServiceRequest(S $service, Provider $provider)
    {
        $this->serviceRequestRepository->addWhere("service", $service->id);
        $this->serviceRequestRepository->addWhere("provider", $provider->id);
        return $this->serviceRequestRepository->findOne();
    }

    public function getRequestConfigByName(Provider $provider, Sr $serviceRequest, string $configItemName)
    {
        return $this->requestConfigRepo->getRequestConfigByName($serviceRequest, $configItemName);
    }

    public function getRequestParametersByRequestName(Provider $provider, string $serviceRequestName = null)
    {
        return $this->requestParametersRepo->getRequestParametersByRequestName($provider, $serviceRequestName);
    }

    public function getResponseKeysByRequest(Provider $provider, Sr $serviceRequest)
    {
        return $this->responseKeysRepo->getResponseKeysByRequest($provider, $serviceRequest);
    }

    private function getServiceRequestObject(Provider $provider, S $service, array $data)
    {
        $fields = [

        ];
        $serviceRequestData = [];
        $categoryRepo = new CategoryRepository();
        $data['service_id'] = $service->id;
        $data['provider_id'] = $provider->id;
        if (!empty($data['pagination_type'])) {
            $paginationType = null;
            if (is_array($data['pagination_type']) && !empty($data['pagination_type']['name'])) {
                $paginationType = $data['pagination_type']['name'];
            } else if (is_string($data['pagination_type'])) {
                $paginationType = $data['pagination_type'];
            }
            $data['pagination_type'] = $paginationType;
        }
        if (!array_key_exists("category", $data) && !array_key_exists("id", $data["category"])) {
            throw new BadRequestHttpException("No category selected.");
        }
        $category = $categoryRepo->findById($data["category"]["id"]);
        $data['category_id'] = $category->id;
        return $data;
    }

    public function createServiceRequest(Provider $provider, array $data)
    {
//        $apiAuthTypeProviderProperty = $this->providerService->getProviderPropertyObjectByName(
//            $provider, "api_authentication_type"
//        );
//        if (
//            !property_exists($apiAuthTypeProviderProperty, "property_value") ||
//            empty($apiAuthTypeProviderProperty->property_value)
//        ) {
//            throw new BadRequestHttpException(
//                "Provider property (api_authentication_type) has to be set before creating a service request."
//            );
//        }
        if (empty($data["label"])) {
            throw new BadRequestHttpException("Service request label is not set.");
        }
        if (empty($data["name"])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }
        $saveServiceRequest = $this->serviceRequestRepository->createServiceRequest($provider, $data);
        if ($saveServiceRequest) {
            $this->requestConfigService->requestConfigValidator($this->serviceRequestRepository->getModel());
        }
        return $saveServiceRequest;

    }

    public function updateServiceRequest(Sr $serviceRequest, array $data)
    {
        return $this->serviceRequestRepository->saveServiceRequest($serviceRequest, $data);
    }

    public function duplicateServiceRequest(Sr $serviceRequest, array $data)
    {
        return $this->serviceRequestRepository->duplicateServiceRequest($serviceRequest, $data);
    }

    public function mergeRequestResponseKeys(array $data)
    {
        $requestResponseKeyRepo = new SrResponseKeyRepository();
        $sourceServiceRequest = $this->getServiceRequestById($data["source_service_request_id"]);
        $destinationServiceRequest = $this->getServiceRequestById($data["destination_service_request_id"]);
        if ($sourceServiceRequest->getService()->id !== $destinationServiceRequest->getService()->id) {
            throw new BadRequestHttpException(
                sprintf(
                    "Service mismatch: Error merging [Service Request: (%s), Service: (%s)] into [Service Request: (%s), Service: (%s)].",
                    $sourceServiceRequest->label, $sourceServiceRequest->getService()->getServiceName(),
                    $destinationServiceRequest->label, $destinationServiceRequest->getService()->getServiceName(),
                )
            );
        }
        return $requestResponseKeyRepo->mergeRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
    }

    public function deleteBatchServiceRequests(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service request ids provided.");
        }
        return $this->serviceRequestRepository->deleteBatch($ids);
    }

    public function deleteServiceRequest(Sr $serviceRequest)
    {
        $this->serviceRequestRepository->setModel($serviceRequest);
        return $this->serviceRequestRepository->delete();
    }

    public function getServiceRequestRepository(): SrRepository
    {
        return $this->serviceRequestRepository;
    }


}
