<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ImportCompletedNotification;
use App\Notifications\ImportStartedNotification;
use App\Services\Tools\IExport\ImportService;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class StartImportJob implements ShouldQueue
{
    use Queueable;

    private ImportService $importService;
    private UserAdminService $userAdminService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public int $fileId,
        public array $mappings
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->importService = App::make(ImportService::class);
        $this->userAdminService = App::make(UserAdminService::class);

        $userId = $this->userId;
        $fileId = $this->fileId;
        $mappings = $this->mappings;

        $user = $this->userAdminService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'ImportMappingsEvent: $user is not instance of User');
            return;
        }

        $user->notify(new ImportStartedNotification($user, $mappings));

        if (empty($fileId)) {
            Log::log('error', 'ImportMappingsEvent: $fileId is empty');
            return;
        }

        if (empty($mappings)) {
            Log::log('error', 'ImportMappingsEvent: $mappings is empty');
            return;
        }

        $this->importService->setUser($user);

        $results = $this->importService->import($fileId, $mappings);

        $user->notify(new ImportCompletedNotification($user, $results));
    }
}
