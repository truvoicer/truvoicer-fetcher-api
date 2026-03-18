<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Services\Provider\ProviderEventService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use RuntimeException;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\SrSchedule;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Repositories\SrResponseKeySrRepository;
use Truvoicer\TfDbReadCore\Repositories\SrScheduleRepository;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\BaseService;

class SrScheduleService extends BaseService
{
    private SrScheduleRepository $srScheduleRepository;

    public function __construct(
        private ProviderEventService $providerEventService,
        private SrService $srService
    ) {
        parent::__construct();
        $this->srScheduleRepository = new SrScheduleRepository;
    }

    public function findByParams(string $sort, string $order, int $count = -1)
    {
        $this->srScheduleRepository->setOrderDir($order);
        $this->srScheduleRepository->setSortField($sort);
        $this->srScheduleRepository->setLimit($count);

        return $this->srScheduleRepository->findMany();
    }

    public function findScheduleForOperationBySr(Sr $serviceRequest)
    {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (! $parentServiceRequest instanceof Sr) {
            return [
                'is_parent' => false,
                'schedule' => $this->findBySr($serviceRequest),
            ];
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->scheduler_override)) {
            return [
                'is_parent' => true,
                'schedule' => $this->findBySr($parentServiceRequest),
            ];
        }

        return [
            'is_parent' => false,
            'schedule' => $this->findBySr($serviceRequest),
        ];
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $this->srScheduleRepository->findBySr($serviceRequest);
    }

    public function createSrSchedule(User $user, Sr $serviceRequest, array $data)
    {
        if (! $this->srScheduleRepository->createSrSchedule($serviceRequest, $data)) {
            return false;
        }

        return $this->runServiceRequest(
            $user,
            $this->srScheduleRepository->getModel(),
            Arr::get($data, 'execute_immediately_choice')
        );
    }

    public function saveSrSchedule(User $user, Sr $sr, array $data)
    {
        $srSchedule = $sr->srSchedule;
        if (! $srSchedule) {
            if (! $this->srScheduleRepository->createSrSchedule($sr, $data)) {
                return false;
            }
        } else {
            $this->srScheduleRepository->setModel($srSchedule);
            if (! $this->srScheduleRepository->saveSrSchedule($data)) {
                return false;
            }
        }

        return $this->runServiceRequest(
            $user,
            $this->srScheduleRepository->getModel(),
            Arr::get($data, 'execute_immediately_choice')
        );
    }

    public function runServiceRequest(User $user, SrSchedule $srSchedule, ?string $executeImmediatelyOp = null)
    {
        if (! $srSchedule->execute_immediately) {
            return true;
        }
        $sr = $srSchedule->sr()->first();
        if (! $sr instanceof Sr || ! $sr->exists) {
            return false;
        }

        /** @var \Truvoicer\TfDbReadCore\Models\Provider|null $provider */
        $provider = $sr->provider()->first();

        if (! $provider) {
            throw new RuntimeException(
                sprintf(
                    'Provider does not exist for this sr. | sr id: %d | sr: name: %s | sr label: %s',
                    $sr->id,
                    $sr->name,
                    $sr->label,
                )
            );
        }
        switch ($executeImmediatelyOp) {
            case 'execute':
                $srOperationsService = App::make(SrOperationsService::class);
                $srOperationsService->setUser($user);
                $srOperationsService->getRequestOperation()->setProvider($provider);
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
