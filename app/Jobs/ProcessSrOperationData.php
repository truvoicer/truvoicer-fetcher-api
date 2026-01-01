<?php

namespace App\Jobs;

use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\Sr;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeySrRepository;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\Entity\ApiResponse;
use Truvoicer\TruFetcherGet\Services\ApiServices\ServiceRequests\SrOperationsService;
use Truvoicer\TruFetcherGet\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSrOperationData implements ShouldQueue
{
    use Queueable;

    private SrOperationsService $srOperationsService;
    private SrService $srService;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $srId,
        public int $userId,
        public array $queryData,
        public ApiResponse $apiResponse,
        public bool $runPagination,
        public bool $runResponseKeySrRequests,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::log('info', 'ProcessSrOperationDataListener');

        $srOperationsService = app(SrOperationsService::class);
        $srService = app(SrService::class);

        $srId = $this->srId;
        $userId = $this->userId;
        $queryData = $this->queryData;
        $apiResponse = $this->apiResponse;
        $runPagination = $this->runPagination;
        $runResponseKeySrRequests = $this->runResponseKeySrRequests;

        if (!is_int($userId)) {
            Log::log('error', 'ProcessSrOperationDataListener: $userId is not int');
            return;
        }
        if (!is_int($srId)) {
            Log::log('error', 'ProcessSrOperationDataListener: $srId is not int');
            return;
        }
        $user = $srService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'ProcessSrOperationDataListener: $user is not instance of User');
            return;
        }
        $sr = $srService->getServiceRequestRepository()->findById($srId);
        if (!$sr instanceof Sr) {
            Log::log('error', 'ProcessSrOperationDataListener: $sr is not instance of Sr');
            return;
        }
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            Log::log('error', 'ProcessSrOperationDataListener: $provider is not instance of Provider');
            return;
        }
        if (!is_array($queryData)) {
            Log::log('error', 'ProcessSrOperationDataListener: $queryData is not array');
            return;
        }
        $srOperationsService->setUser($user);
        $srOperationsService->getRequestOperation()->setProvider($provider);
        $srOperationsService->setRunPagination($runPagination);
        $srOperationsService->setRunResponseKeySrRequests($runResponseKeySrRequests);
        $saveData = $srOperationsService->processByType(
            $sr,
            SrResponseKeySrRepository::ACTION_STORE,
            $queryData,
            $apiResponse
        );
    }
}
