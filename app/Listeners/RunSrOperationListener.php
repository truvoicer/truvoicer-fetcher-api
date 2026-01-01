<?php

namespace App\Listeners;

use Truvoicer\TruFetcherGet\Events\RunSrOperationEvent;
use App\Jobs\SrOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RunSrOperationListener implements ShouldQueue
{

    use InteractsWithQueue;

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
    public function handle(RunSrOperationEvent $event): void
    {
        Log::log('info', 'RunOperationListener');
        SrOperation::dispatch(
            $event->userId,
            $event->srId,
            $event->queryData
        );
    }
}
