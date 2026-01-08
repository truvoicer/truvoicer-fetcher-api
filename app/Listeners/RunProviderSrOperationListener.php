<?php

namespace App\Listeners;

use App\Events\RunProviderSrOperationEvent;
use App\Jobs\ProviderSrOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RunProviderSrOperationListener
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
    public function handle(RunProviderSrOperationEvent $event): void
    {
        Log::log('info', 'RunProviderSrOperationEvent');
        ProviderSrOperation::dispatch(
            $event->userId,
            $event->providerId,
            $event->interval,
            $event->executeImmediately,
        )->onConnection('database-tf');
    }
}
