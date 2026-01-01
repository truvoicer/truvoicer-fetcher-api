<?php

namespace App\Providers;

use Truvoicer\TfDbReadCore\Models\Provider;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as RoutingRoute;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::model('user', User::class);
        Route::model('provider', Provider::class);

        Route::bind('serviceRequest', function ($value, RoutingRoute $ssd) {
            $provider = $ssd->parameter('provider');
            return $provider->serviceRequest()->where('id', $value)->firstOrFail();
        });

        Route::bind('childSr', function (int $value, RoutingRoute $ssd) {
            $serviceRequest = $ssd->parameter('serviceRequest');
            return $serviceRequest->childSrs()->where('sr_child_id', $value)->firstOrFail();
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
