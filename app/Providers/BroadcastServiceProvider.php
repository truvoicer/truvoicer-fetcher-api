<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:sanctum', 'ability:api:admin,api:superuser,api:super_admin,api:user,api:app_user']]);

        require base_path('routes/channels.php');
    }
}
