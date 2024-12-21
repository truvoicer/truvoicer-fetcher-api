<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ImportCompletedNotification;
use App\Services\User\UserAdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class CompleteImportJob implements ShouldQueue
{
    use Queueable;

    private UserAdminService $userAdminService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public array $results
    )
    {
        $this->userAdminService = App::make(UserAdminService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userId = $this->userId;
        $results = $this->results;

        $user = $this->userAdminService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'ImportCompletedListener: $user is not instance of User');
            return;
        }

        if (empty($results)) {
            Log::log('error', 'ImportCompletedListener: $results is empty');
            return;
        }
        $user->notify(new ImportCompletedNotification($user, $results));
    }
}
