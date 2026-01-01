<?php

namespace App\Providers;

use Truvoicer\TfDbReadCore\Events\ProcessSrOperationDataEvent;
use Truvoicer\TfDbReadCore\Events\RunProviderSrOperationEvent;
use Truvoicer\TfDbReadCore\Events\RunSrOperationEvent;
use App\Listeners\ProcessSrOperationDataListener;
use App\Listeners\RunProviderSrOperationListener;
use App\Listeners\RunSrOperationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        RunProviderSrOperationEvent::class => [
            RunProviderSrOperationListener::class,
        ],
        RunSrOperationEvent::class => [
            RunSrOperationListener::class,
        ],
        ProcessSrOperationDataEvent::class => [
            ProcessSrOperationDataListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
