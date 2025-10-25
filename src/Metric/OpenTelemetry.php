<?php

namespace Nadi\Laravel\Metric;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Metric\Base;

/**
 * OpenTelemetry-specific metrics for Laravel applications
 *
 * This metric class provides OpenTelemetry semantic convention attributes
 * specific to Laravel applications, including resource attributes and
 * telemetry SDK information.
 */
class OpenTelemetry extends Base
{
    public function metrics(): array
    {
        $metrics = [
            // Service identification
            'service.name' => config('nadi.connections.opentelemetry.service_name', config('app.name', 'laravel-app')),
            'service.version' => config('nadi.connections.opentelemetry.service_version', '1.0.0'),
            'deployment.environment' => config('nadi.connections.opentelemetry.deployment_environment', config('app.env', 'production')),

            // Telemetry SDK information
            'telemetry.sdk.name' => 'nadi-laravel',
            'telemetry.sdk.language' => 'php',
            'telemetry.sdk.version' => $this->getNadiVersion(),

            // Laravel framework information
            'laravel.version' => app()->version(),
            'laravel.environment' => app()->environment(),
            'laravel.debug' => config('app.debug', false),
            'laravel.timezone' => config('app.timezone', 'UTC'),
            'laravel.locale' => config('app.locale', 'en'),

            // Host and process information
            'host.name' => gethostname() ?: 'unknown',
            'process.pid' => getmypid(),
            'process.runtime.name' => 'php',
            'process.runtime.version' => PHP_VERSION,
            'process.runtime.description' => 'PHP '.PHP_VERSION,
        ];

        // Add optional service namespace and instance ID if configured
        if ($namespace = config('nadi.connections.opentelemetry.service_namespace')) {
            $metrics['service.namespace'] = $namespace;
        }

        if ($instanceId = config('nadi.connections.opentelemetry.service_instance_id')) {
            $metrics['service.instance.id'] = $instanceId;
        }

        // Add container information if available
        if ($this->isRunningInContainer()) {
            $metrics['container.runtime'] = $this->getContainerRuntime();

            if ($containerId = $this->getContainerId()) {
                $metrics['container.id'] = $containerId;
            }
        }

        // Add cloud provider information if available
        $cloudMetrics = $this->getCloudProviderMetrics();
        if (! empty($cloudMetrics)) {
            $metrics = array_merge($metrics, $cloudMetrics);
        }

        // Add user context if available during web requests
        if (function_exists('request') && request()) {
            $userAttributes = OpenTelemetrySemanticConventions::userAttributes();
            $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();
            $metrics = array_merge($metrics, $userAttributes, $sessionAttributes);
        }

        return $metrics;
    }

    /**
     * Get the Nadi version from composer
     */
    private function getNadiVersion(): string
    {
        try {
            $composerPath = base_path('vendor/composer/installed.php');
            if (file_exists($composerPath)) {
                $installed = include $composerPath;
                $packages = $installed['versions'] ?? [];

                if (isset($packages['nadi-pro/nadi-laravel']['version'])) {
                    return $packages['nadi-pro/nadi-laravel']['version'];
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors
        }

        return '1.0.0'; // fallback version
    }

    /**
     * Check if running in a container
     */
    private function isRunningInContainer(): bool
    {
        return file_exists('/.dockerenv') ||
               file_exists('/proc/1/cgroup') &&
               strpos(file_get_contents('/proc/1/cgroup'), 'docker') !== false;
    }

    /**
     * Get container runtime
     */
    private function getContainerRuntime(): string
    {
        if (file_exists('/.dockerenv')) {
            return 'docker';
        }

        if (getenv('KUBERNETES_SERVICE_HOST')) {
            return 'kubernetes';
        }

        return 'container';
    }

    /**
     * Get container ID
     */
    private function getContainerId(): ?string
    {
        try {
            if (file_exists('/proc/self/cgroup')) {
                $cgroup = file_get_contents('/proc/self/cgroup');
                if (preg_match('/\/docker\/([a-f0-9]{64})/', $cgroup, $matches)) {
                    return substr($matches[1], 0, 12); // Short container ID
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors
        }

        return null;
    }

    /**
     * Get cloud provider metrics
     */
    private function getCloudProviderMetrics(): array
    {
        $metrics = [];

        // AWS metadata
        if ($this->isAWS()) {
            $metrics['cloud.provider'] = 'aws';

            if ($region = $this->getAWSRegion()) {
                $metrics['cloud.region'] = $region;
            }

            if ($instanceId = $this->getAWSInstanceId()) {
                $metrics['cloud.resource_id'] = $instanceId;
            }
        }

        // Google Cloud metadata
        if ($this->isGCP()) {
            $metrics['cloud.provider'] = 'gcp';

            if ($region = $this->getGCPRegion()) {
                $metrics['cloud.region'] = $region;
            }

            if ($instanceId = $this->getGCPInstanceId()) {
                $metrics['cloud.resource_id'] = $instanceId;
            }
        }

        // Azure metadata
        if ($this->isAzure()) {
            $metrics['cloud.provider'] = 'azure';

            if ($region = $this->getAzureRegion()) {
                $metrics['cloud.region'] = $region;
            }
        }

        return $metrics;
    }

    /**
     * Check if running on AWS
     */
    private function isAWS(): bool
    {
        return getenv('AWS_REGION') !== false ||
               getenv('AWS_DEFAULT_REGION') !== false ||
               $this->checkMetadataEndpoint('http://169.254.169.254/latest/meta-data/');
    }

    /**
     * Check if running on Google Cloud Platform
     */
    private function isGCP(): bool
    {
        return getenv('GOOGLE_CLOUD_PROJECT') !== false ||
               $this->checkMetadataEndpoint('http://169.254.169.254/computeMetadata/v1/', ['Metadata-Flavor: Google']);
    }

    /**
     * Check if running on Azure
     */
    private function isAzure(): bool
    {
        return getenv('AZURE_CLIENT_ID') !== false ||
               $this->checkMetadataEndpoint('http://169.254.169.254/metadata/instance', ['Metadata: true']);
    }

    /**
     * Check metadata endpoint availability
     */
    private function checkMetadataEndpoint(string $url, array $headers = []): bool
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1,
                    'header' => implode("\r\n", $headers),
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            return $response !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get AWS region
     */
    private function getAWSRegion(): ?string
    {
        return getenv('AWS_REGION') ?: getenv('AWS_DEFAULT_REGION') ?: null;
    }

    /**
     * Get AWS instance ID
     */
    private function getAWSInstanceId(): ?string
    {
        try {
            $context = stream_context_create([
                'http' => ['method' => 'GET', 'timeout' => 1],
            ]);

            return @file_get_contents('http://169.254.169.254/latest/meta-data/instance-id', false, $context) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get GCP region
     */
    private function getGCPRegion(): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1,
                    'header' => 'Metadata-Flavor: Google',
                ],
            ]);
            $zone = @file_get_contents('http://169.254.169.254/computeMetadata/v1/instance/zone', false, $context);

            return $zone ? basename($zone) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get GCP instance ID
     */
    private function getGCPInstanceId(): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1,
                    'header' => 'Metadata-Flavor: Google',
                ],
            ]);

            return @file_get_contents('http://169.254.169.254/computeMetadata/v1/instance/id', false, $context) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Azure region
     */
    private function getAzureRegion(): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1,
                    'header' => 'Metadata: true',
                ],
            ]);
            $metadata = @file_get_contents('http://169.254.169.254/metadata/instance/compute/location?api-version=2021-02-01&format=text', false, $context);

            return $metadata ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
