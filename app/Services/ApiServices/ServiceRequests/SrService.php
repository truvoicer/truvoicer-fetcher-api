<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\S;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Repositories\CategoryRepository;
use App\Repositories\SrChildSrRepository;
use App\Repositories\SRepository;
use App\Repositories\SrConfigRepository;
use App\Repositories\SrParameterRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrScheduleRepository;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\RateLimitService;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use App\Services\Task\ScheduleService;
use App\Services\Tools\HttpRequestService;
use App\Helpers\Tools\UtilHelpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrService extends BaseService
{
    private SrRepository $serviceRequestRepository;
    private SResponseKeyRepository $responseKeysRepo;
    private SrChildSrRepository $srChildSrRepository;

    public function __construct(
    )
    {
        parent::__construct();
        $this->serviceRequestRepository = new SrRepository();
        $this->responseKeysRepo = new SResponseKeyRepository();
        $this->srChildSrRepository = new SrChildSrRepository();
    }

    public function findParentSr(Sr $serviceRequest) {
        $parentServiceRequest = $serviceRequest->parentSrs()->first();
        if (!$parentServiceRequest instanceof Sr) {
            return false;
        }
        return $parentServiceRequest;
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
        return $this->serviceRequestRepository::getSrByName($provider, $serviceRequestName);
    }

    public function getServiceRequestById($id)
    {
        $getServiceRequest = $this->serviceRequestRepository->findById($id);
        if ($getServiceRequest === null) {
            throw new BadRequestHttpException("Service request does not exist in database.");
        }
        return $getServiceRequest;
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

    public function getUserChildSrsByProvider(Provider $provider, Sr $sr, string $sort, string $order, ?int $count = null)
    {
        return $this->serviceRequestRepository->getChildSrs(
            $sr,
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



    public function createServiceRequest(Provider $provider, array $data, ?bool $validateConfig = true)
    {
        if (empty($data["label"])) {
            throw new BadRequestHttpException("Service request label is not set.");
        }
        if (empty($data["name"])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }
        $requestConfigService = App::make(SrConfigService::class);
        $saveServiceRequest = $this->serviceRequestRepository->createServiceRequest($provider, $data);
        if ($saveServiceRequest && $validateConfig) {
            $requestConfigService->requestConfigValidator($this->serviceRequestRepository->getModel());
        }
        return $saveServiceRequest;

    }

    public function createChildSr(Provider $provider, Sr $sr, array $data)
    {
        $saveServiceRequest = $this->createServiceRequest(
            $provider,
            $this->serviceRequestRepository->buildSaveData($data, $sr)
        );
        if (!$saveServiceRequest) {
            return false;
        }
        $childSr = $this->serviceRequestRepository->getModel();
        if (!$childSr instanceof Sr) {
            return false;
        }
        return $this->srChildSrRepository->saveParentChildSr(
            $sr,
            $childSr
        );
    }

    public function updateServiceRequest(Sr $serviceRequest, array $data)
    {
        return $this->serviceRequestRepository->saveServiceRequest($serviceRequest, $data);
    }

    public function overrideChildSr(Sr $serviceRequest, array $data)
    {
        $saveData = [
            $data['key'] => $data['value']
        ];
        return $this->serviceRequestRepository->saveChildSrOverrides($serviceRequest, $saveData);
    }

    public function duplicateServiceRequest(Sr $serviceRequest, array $data)
    {
        if (empty($data["label"])) {
            throw new BadRequestHttpException("Service request label is not set.");
        }
        if (empty($data["name"])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }
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
