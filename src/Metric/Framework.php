<?php

namespace Nadi\Laravel\Metric;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Metric\Base;

class Framework extends Base
{
    public function metrics(): array
    {
        return [
            'framework.name' => 'laravel',
            'framework.version' => app()->version(),
            // Add OpenTelemetry service identification
            OpenTelemetrySemanticConventions::SERVICE_NAME => config('nadi.connections.opentelemetry.service_name', config('app.name', 'laravel-app')),
            OpenTelemetrySemanticConventions::SERVICE_VERSION => config('nadi.connections.opentelemetry.service_version', '1.0.0'),
            OpenTelemetrySemanticConventions::DEPLOYMENT_ENVIRONMENT => config('nadi.connections.opentelemetry.deployment_environment', config('app.env', 'production')),
        ];
    }
}
