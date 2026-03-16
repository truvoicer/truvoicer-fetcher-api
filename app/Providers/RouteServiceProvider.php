<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\User;

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

        /**
         * Bind serviceRequest to a provider's service requests.
         *
         * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
         */
        Route::bind('serviceRequest', function (string $value, RoutingRoute $route) {
            /** @var \Truvoicer\TfDbReadCore\Models\Provider|null $provider */
            $provider = $route->parameter('provider');

            if (!$provider instanceof Provider) {
                abort(404, 'Provider not found or invalid.');
            }

            /** @var \Truvoicer\TfDbReadCore\Models\Sr|null $serviceRequest */
            $serviceRequest = $provider->serviceRequest()
                ->where('id', $value)
                ->first();

            if (!$serviceRequest) {
                abort(404, 'Service request not found for this provider.');
            }

            return $serviceRequest;
        });

        /**
         * Bind childSr to a service request's child service requests.
         *
         * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
         */
        Route::bind('childSr', function (string $value, RoutingRoute $route) {
            /** @var \Truvoicer\TfDbReadCore\Models\Sr|null $serviceRequest */
            $serviceRequest = $route->parameter('serviceRequest');

            if (!$serviceRequest instanceof Sr) {
                abort(404, 'Service request not found or invalid.');
            }

            /** @var \Truvoicer\TfDbReadCore\Models\Sr|null $childSr */
            $childSr = $serviceRequest->childSrs()
                ->where('sr_child_id', $value)
                ->first();

            if (!$childSr) {
                abort(404, 'Child service request not found.');
            }

            return $childSr;
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
