<?php

namespace Nadi\Laravel\Metric;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Metric\Base;

class Application extends Base
{
    public function metrics(): array
    {
        $metrics = [
            'app.controller.action' => optional(request()->route())->getActionName(),
            'app.middleware' => array_values(optional(request()->route())->gatherMiddleware() ?? []),
            'app.environment' => app()->environment(),
        ];

        // Add OpenTelemetry semantic convention attributes
        if (function_exists('request') && request()) {
            $route = request()->route();
            if ($route) {
                // Add route information using OTel conventions
                if ($routeName = $route->getName()) {
                    $metrics[OpenTelemetrySemanticConventions::LARAVEL_ROUTE_NAME] = $routeName;
                }

                if ($action = $route->getActionName()) {
                    $metrics[OpenTelemetrySemanticConventions::LARAVEL_ROUTE_ACTION] = $action;

                    // Extract controller name
                    if (strpos($action, '@') !== false) {
                        $metrics[OpenTelemetrySemanticConventions::LARAVEL_CONTROLLER] = explode('@', $action)[0];
                    }
                }

                // Add middleware with OTel convention
                $middleware = $route->gatherMiddleware();
                if (! empty($middleware)) {
                    $metrics[OpenTelemetrySemanticConventions::LARAVEL_MIDDLEWARE] = implode(',', $middleware);
                }
            }
        }

        return $metrics;
    }
}
