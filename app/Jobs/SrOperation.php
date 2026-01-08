<?php

namespace App\Jobs;

use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Repositories\SrResponseKeySrRepository;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SrOperation implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public int     $srId,
        public array $queryData
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::log('info', 'RunSsdsdsdsdrOperationJob');

        $srOperationsService = app(SrOperationsService::class);
        $srService = app(SrService::class);

        $srId = $this->srId;
        $userId = $this->userId;
        $queryData = $this->queryData;
        if (!is_int($userId)) {
            Log::log('error', 'RunSrOperationJob: $userId is not int');
            return;
        }
        if (!is_int($srId)) {
            Log::log('error', 'RunSrOperationJob: $srId is not int');
            return;
        }
        $user = $srService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'RunSrOperationJob: $user is not instance of User');
            return;
        }
        $sr = $srService->getServiceRequestRepository()->findById($srId);
        if (!$sr instanceof Sr) {
            Log::log('error', 'RunSrOperationJob: $sr is not instance of Sr');
            return;
        }
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            Log::log('error', 'RunSrOperationJob: $provider is not instance of Provider');
            return;
        }
        if (!is_array($queryData)) {
            Log::log('error', 'RunSrOperationJob: $queryData is not array');
            return;
        }
        $srOperationsService->setUser($user);
        $srOperationsService->getRequestOperation()->setProvider($provider);
        $srOperationsService->runOperationForSr(
            $sr,
            SrResponseKeySrRepository::ACTION_STORE,
            $queryData
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SrOperation job failed', [
            'user_id' => $this->userId,
            'sr_id' => $this->srId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optional: notify admin or mark SR as failed
    }
}
