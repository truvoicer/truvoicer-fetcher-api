<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\S;
use App\Models\Provider;
use App\Models\Sr;
use App\Repositories\SrChildSrRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Repositories\SResponseKeyRepository;
use App\Services\BaseService;
use App\Helpers\Tools\UtilHelpers;
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
    public function getDefaultSr(Provider $provider, string $type)
    {
        return  $provider->sr()
            ->where('default_sr', true)
            ->where('type', $type)
            ->first();
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
        $this->serviceRequestRepository->setPagination(true);
        return $this->serviceRequestRepository->getServiceRequestByProvider(
            $provider,
            $sort,
            $order,
            $count
        );
    }

    public function getUserChildSrsByProvider(Provider $provider, Sr $sr, string $sort, string $order, ?int $count = null)
    {
        $this->serviceRequestRepository->setPagination(true);
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
        if (!empty($data['default_sr'])) {
            $providerSrs = $provider->sr()
                ->where('default_sr', true)
                ->where('type', $data['type'])
                ->get();
            if ($providerSrs->count() > 0) {
                foreach ($providerSrs as $providerSr) {
                    $providerSr->default_sr = false;
                    $providerSr->save();
                }
            }
        }
        $saveServiceRequest = $this->serviceRequestRepository->createServiceRequest($provider, $data);
        if ($saveServiceRequest && $validateConfig) {
            $requestConfigService = App::make(SrConfigService::class);
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
        if (!empty($data['default_sr'])) {
            $provider = $serviceRequest->provider;
            $providerSrs = $provider->sr()
                ->where('default_sr', true)
                ->where('type', $serviceRequest->type)
                ->where('id', '!=', $serviceRequest->id)
                ->get();
            if ($providerSrs->count() > 0) {
                foreach ($providerSrs as $providerSr) {
                    $providerSr->default_sr = false;
                    $providerSr->save();
                }
            }
        }
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
