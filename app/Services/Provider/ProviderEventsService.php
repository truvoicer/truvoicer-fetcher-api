<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\SrSchedule;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Task\ScheduleService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class ProviderEventsService
{
    private MongoDBRepository $mongoDBRepository;
    private ScheduleService $scheduleService;
    private SrService $srService;
    private ProviderService $providerService;
    private RequestOperation $requestOperation;
    public function __construct(
        ScheduleService $scheduleService,
        SrService $srService,
        ProviderService $providerService,
        RequestOperation $requestOperation,
    ) {
        $this->scheduleService = $scheduleService;
        $this->srService = $srService;
        $this->providerService = $providerService;
        $this->requestOperation = $requestOperation;
        $this->mongoDBRepository = new MongoDBRepository();
    }

    public function providerSrSchedule(Schedule $schedule)
    {
        $this->scheduleService->setSchedule($schedule);
        $providers = $this->providerService->getProviderRepository()->findAll();
        foreach ($providers as $provider) {
            $this->scheduleService->run(
                function ($interval, $params) use ($provider) {
                    $srs = $this->getSrsByScheduleInterval($provider, $interval['field']);
                    if ($srs->count() === 0) {
                        return;
                    }
                    $this->runSrOperations($srs, $provider);
                }
            );
        }
    }

    public function getSrsByScheduleInterval(Provider $provider, string $interval)
    {
        if (!array_key_exists($interval, ScheduleService::SCHEDULE_INTERVALS)) {
            return false;
        }
        if (!in_array($interval, SrSchedule::FIELDS)) {
            return false;
        }
        return $this->srService->getSrScheduleRepository()->fetchSrsByScheduleInterval($provider, $interval);
    }
    public function runSrOperations(Collection $serviceRequests, Provider $provider)
    {
        $this->requestOperation->setProvider($provider);
        $user = $provider->providerUser()->first()->user()->first();
        $this->requestOperation->setUser($user);
        foreach ($serviceRequests as $serviceRequest) {
            $this->requestOperation->setSr($serviceRequest);
            $operationData = $this->requestOperation->runOperation(['query' => '']);
            if ($operationData->getStatus() !== 'success') {
                continue;
            }
            $data = $operationData->getRequestData();
            $this->mongoDBRepository->setCollection($serviceRequest->s()->first()->name);
            foreach ($data as $item) {
                $this->mongoDBRepository->insert($item);
            }
            dd($operationData);
        }
    }
}
