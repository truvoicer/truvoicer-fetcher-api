<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use \Illuminate\Routing\Route as RoutingRoute;

class RouteService
{
    /**
     * @return array<string, mixed>
     */
    public static function getRoutes(?string $prefix = null): array
    {

        $routes = Route::getRoutes()->getRoutes();
        $routes = collect($routes)->filter(function (RoutingRoute $route) use ($prefix) {
            if ($prefix !== null) {
                return $route->getName() !== null && str_starts_with($route->getName(), $prefix);
            }
            return $route->getName() !== null;
        });
        $routes = $routes->map(function ($route) {
            $methods = $route->methods();
            if (in_array('HEAD', $methods)) {
                unset($methods[array_search('HEAD', $methods)]);
            }
            return [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => array_values($methods),
//                'action' => $route->getActionName(),
//                'middleware' => $route->middleware(),
            ];
        });
        return array_values($routes->toArray());
    }
}
