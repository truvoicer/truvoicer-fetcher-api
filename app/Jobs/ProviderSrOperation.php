<?php

namespace App\Jobs;

use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\User;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;
use Truvoicer\TfDbReadCore\Services\Task\ScheduleService;

class ProviderSrOperation implements ShouldQueue
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
        public int $providerId,
        public string $interval,
        public ?bool $executeImmediately = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::log('info', 'RunSrOperationListener');

        $srOperationsService = app(SrOperationsService::class);
        $providerService = app(ProviderService::class);

        $providerId = $this->providerId;
        $userId = $this->userId;
        $interval = $this->interval;
        $executeImmediately = $this->executeImmediately;
        if (!is_int($userId)) {
            Log::log('error', 'RunSrOperationListener: $userId is not int');
            return;
        }
        if (!is_int($providerId)) {
            Log::log('error', 'RunSrOperationListener: $providerId is not int');
            return;
        }
        $user = $providerService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'RunSrOperationListener: $user is not instance of User');
            return;
        }
        $provider = $providerService->getProviderRepository()->findById($providerId);
        if (!$provider instanceof Provider) {
            Log::log('error', 'RunSrOperationListener: $provider is not instance of Provider');
            return;
        }
        if (!is_string($interval) || !array_key_exists($interval, ScheduleService::SCHEDULE_INTERVALS)) {
            Log::log('error', 'RunSrOperationListener: $interval is not string');
            return;
        }
        $srOperationsService->setUser($user);
        $srOperationsService->runSrOperationsByInterval($provider, $interval, $executeImmediately);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProviderSrOperation job failed', [
            'user_id' => $this->userId,
            'provider_id' => $this->providerId,
            'interval' => $this->interval,
            'executeImmediately' => $this->executeImmediately,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
