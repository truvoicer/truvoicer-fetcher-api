<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Sr;
use App\Models\SrSchedule;
use App\Models\User;
use App\Repositories\SrResponseKeySrRepository;
use App\Repositories\SrScheduleRepository;
use App\Services\BaseService;
use App\Services\Provider\ProviderEventService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class SrScheduleService extends BaseService
{
    private SrScheduleRepository $srScheduleRepository;
    public function __construct(
        private ProviderEventService $providerEventService,
        private SrService $srService
    )
    {
        parent::__construct();
        $this->srScheduleRepository = new SrScheduleRepository();
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->srScheduleRepository->setOrderDir($order);
        $this->srScheduleRepository->setSortField($sort);
        $this->srScheduleRepository->setLimit($count);
        return $this->srScheduleRepository->findMany();
    }

    public function findScheduleForOperationBySr(Sr $serviceRequest) {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (!$parentServiceRequest instanceof Sr) {
            return [
                'is_parent' => false,
                'schedule' => $this->findBySr($serviceRequest)
            ];
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->scheduler_override)) {
            return [
                'is_parent' => true,
                'schedule' => $this->findBySr($parentServiceRequest)
            ];
        }
        return [
            'is_parent' => false,
            'schedule' => $this->findBySr($serviceRequest)
        ];
    }
    public function findBySr(Sr $serviceRequest)
    {
        return $this->srScheduleRepository->findBySr($serviceRequest);
    }

    public function createSrSchedule(User $user, Sr $serviceRequest, array $data)
    {
        if (!$this->srScheduleRepository->createSrSchedule($serviceRequest, $data)) {
            return false;
        }
        return $this->runServiceRequest(
            $user,
            $this->srScheduleRepository->getModel(),
            Arr::get($data, 'execute_immediately_choice')
        );
    }
    public function saveSrSchedule(User $user, SrSchedule $srSchedule, array $data)
    {
        $this->srScheduleRepository->setModel($srSchedule);
        if (!$this->srScheduleRepository->saveSrSchedule($data)) {
            return false;
        }
        return $this->runServiceRequest(
            $user,
            $this->srScheduleRepository->getModel(),
            Arr::get($data, 'execute_immediately_choice')
        );
    }

    public function runServiceRequest(User $user, SrSchedule $srSchedule, ?string $executeImmediatelyOp = null) {
        if (!$srSchedule->execute_immediately) {
            return true;
        }
        $sr = $srSchedule->sr()->first();
        if (!$sr instanceof Sr || !$sr->exists) {
            return false;
        }

        switch ($executeImmediatelyOp) {
            case 'execute':
                $srOperationsService = App::make(SrOperationsService::class);
                $srOperationsService->setUser($user);
                $srOperationsService->getRequestOperation()->setProvider($sr->provider()->first());
                $srOperationsService->runOperationForSr($sr, SrResponseKeySrRepository::ACTION_STORE);
                break;
            default:
                $this->providerEventService->dispatchSrOperationEvent($user, $sr);
                break;

        }
        return true;
    }

    public function getSrScheduleById(int $id)
    {
        return $this->srScheduleRepository->findById($id);
    }

    public function deleteSrSchedule(SrSchedule $srSchedule)
    {
        return $this->srScheduleRepository->deleteSrSchedule($srSchedule);
    }

    public function getSrScheduleRepository(): SrScheduleRepository
    {
        return $this->srScheduleRepository;
    }

}
