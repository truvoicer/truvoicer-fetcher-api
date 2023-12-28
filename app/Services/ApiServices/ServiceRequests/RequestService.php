<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Service;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestConfigRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceRequestResponseKeyRepository;
use App\Repositories\ServiceResponseKeyRepository;
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
    private ServiceRepository $serviceRepository;
    private ServiceRequestRepository $serviceRequestRepository;
    private ServiceRequestParameterRepository $requestParametersRepo;
    private ServiceRequestConfigRepository $requestConfigRepo;
    private ServiceResponseKeyRepository $responseKeysRepo;
    private RequestConfigService $requestConfigService;
    private RequestParametersService $requestParametersService;
    private ApiService $apiService;

    public function __construct(HttpRequestService $httpRequestService,
                                ProviderService $providerService, RequestConfigService $requestConfigService,
                                RequestParametersService $requestParametersService, ApiService $apiService
    )
    {
        $this->httpRequestService = $httpRequestService;
        $this->providerService = $providerService;
        $this->apiService = $apiService;
        $this->requestConfigService = $requestConfigService;
        $this->requestParametersService = $requestParametersService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->responseKeysRepo = new ServiceResponseKeyRepository();
        $this->requestConfigRepo = new ServiceRequestConfigRepository();
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRequestRepository->findByQuery($query);
    }

    public function findByParams(string $sort, string $order, int $count)
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

    public function castServiceRequest(ServiceRequest $serviceRequest)
    {
        return $serviceRequest;
    }

    public function getUserServiceRequestByProvider(Provider $provider, string $sort, string $order, int $count)
    {
        return $this->serviceRequestRepository->getServiceRequestByProvider(
            $provider,
            $sort,
            $order,
            $count
        );
    }

    public function getProviderServiceRequest(Service $service, Provider $provider)
    {
        $this->serviceRequestRepository->addWhere("service", $service->id);
        $this->serviceRequestRepository->addWhere("provider", $provider->id);
        return $this->serviceRequestRepository->findOne();
    }

    public function getRequestConfigByName(Provider $provider, ServiceRequest $serviceRequest, string $configItemName)
    {
        return $this->requestConfigRepo->getRequestConfigByName($provider, $serviceRequest, $configItemName);
    }

    public function getRequestParametersByRequestName(Provider $provider, string $serviceRequestName = null)
    {
        return $this->requestParametersRepo->getRequestParametersByRequestName($provider, $serviceRequestName);
    }

    public function getResponseKeysByRequest(Provider $provider, ServiceRequest $serviceRequest)
    {
        return $this->responseKeysRepo->getResponseKeys($provider, $serviceRequest);
    }

    private function getServiceRequestObject(Provider $provider, Service $service, array $data)
    {
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
        $apiAuthTypeProviderProperty = $this->providerService->getProviderPropertyObjectByName(
            $provider, "api_authentication_type"
        );
        if (
            !property_exists($apiAuthTypeProviderProperty, "property_value") ||
            empty($apiAuthTypeProviderProperty->property_value)
        ) {
            throw new BadRequestHttpException(
                "Provider property (api_authentication_type) has to be set before creating a service request."
            );
        }
        if (empty($data["label"])) {
            throw new BadRequestHttpException("Service request label is not set.");
        }
        $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        $service = $this->serviceRepository->findById($data["service_id"]);
        $saveServiceRequest = $this->serviceRequestRepository->save($this->getServiceRequestObject($provider, $service, $data));
        if ($saveServiceRequest) {
            $this->requestConfigService->requestConfigValidator($this->serviceRequestRepository->getModel());
        }
        return $saveServiceRequest;

    }

    public function updateServiceRequest(Provider $provider, ServiceRequest $serviceRequest, array $data)
    {
        if (isset($data["service"]['id'])) {
            $serviceId = $data["service"]['id'];
        } elseif (isset($data["service_id"])) {
            $serviceId = $data["service_id"];
        } else {
            throw new BadRequestHttpException("Service id is not set.");
        }
        $service = $this->serviceRepository->find($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException("Invalid service in request");
        }
        return $this->serviceRequestRepository->save($this->getServiceRequestObject($provider, $service, $data));
    }

    public function duplicateServiceRequest(ServiceRequest $serviceRequest, array $data)
    {
        return $this->serviceRequestRepository->duplicateServiceRequest($serviceRequest, $data);
    }

    public function mergeRequestResponseKeys(array $data)
    {
        $requestResponseKeyRepo = new ServiceRequestResponseKeyRepository();
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

    public function deleteServiceRequestById(int $id)
    {
        $serviceRequest = $this->serviceRequestRepository->findById($id);
        if ($serviceRequest === null) {
            throw new BadRequestHttpException(sprintf("Service request id: %s not found in database.", $id));
        }
        return $this->deleteServiceRequest($serviceRequest);
    }

    public function deleteServiceRequest(ServiceRequest $serviceRequest)
    {
        $this->serviceRequestRepository->setModel($serviceRequest);
        return $this->serviceRequestRepository->delete();
    }


}
