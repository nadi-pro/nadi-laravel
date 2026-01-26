<?php

namespace Nadi\Laravel\Metric;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Metric\Base;

class Application extends Base
{
    public function metrics(): array
    {
        $metrics = [
            'app.environment' => app()->environment(),
        ];

        // Only add request-specific metrics if in HTTP context
        if (function_exists('request') && request() && request()->route()) {
            $route = request()->route();

            $metrics['app.controller.action'] = $route->getActionName();
            $metrics['app.middleware'] = array_values($route->gatherMiddleware() ?? []);

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
        } elseif (app()->runningInConsole()) {
            // Add console-specific context
            $metrics['app.context'] = 'console';

            // Try to get command name if available
            if (isset($_SERVER['argv'])) {
                $metrics['app.command'] = implode(' ', array_slice($_SERVER['argv'], 1));
            }
        }

        return $metrics;
    }
}
