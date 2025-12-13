<?php

namespace App\Listeners;

use App\Events\ProcessSrOperationDataEvent;
use App\Jobs\ProcessSrOperationData;

class ProcessSrOperationDataListener
{

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120; // Allow 2 minutes for this listener to run

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(ProcessSrOperationDataEvent $event): void
    {
        ProcessSrOperationData::dispatch(
            $event->srId,
            $event->userId,
            $event->queryData,
            $event->apiResponse,
            $event->runPagination,
            $event->runResponseKeySrRequests,
        );
    }
}
