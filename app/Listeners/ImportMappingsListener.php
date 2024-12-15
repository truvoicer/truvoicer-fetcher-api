<?php

namespace App\Listeners;

use App\Events\ImportMappingsEvent;
use App\Models\User;
use App\Services\Tools\IExport\ImportService;
use App\Services\User\UserAdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ImportMappingsListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private ImportService $importService,
        private UserAdminService $userAdminService,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportMappingsEvent $event): void
    {
        $userId = $event->userId;
        $fileId = $event->fileId;
        $mappings = $event->mappings;

        $user = $this->userAdminService->getUserRepository()->findById($userId);
        if (!$user instanceof User) {
            Log::log('error', 'ImportMappingsEvent: $user is not instance of User');
            return;
        }
        if (empty($fileId)) {
            Log::log('error', 'ImportMappingsEvent: $fileId is empty');
            return;
        }

        if (empty($mappings)) {
            Log::log('error', 'ImportMappingsEvent: $mappings is empty');
            return;
        }

        $this->importService->setUser($user);
        $this->importService->runMappingsImporter($fileId, $mappings);
    }
}
