<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;

class VerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Nadi Configuration for the Application.';

    public function handle()
    {
        $this->info('🔍 Verifying Nadi configuration...');
        $this->newLine();

        $isEnabled = config('nadi.enabled');
        $driver = config('nadi.driver');

        // Check if Nadi is enabled
        $this->checkEnabled($isEnabled);

        // Check driver configuration
        $this->checkDriverConfiguration($driver);

        // Check sampling configuration
        $this->checkSamplingConfiguration();

        // Run verification test
        $verificationResult = app('nadi')->verify();

        $this->newLine();
        if ($verificationResult) {
            $this->info('✅ Application Verification Status: <info>OK</info>');
            $this->displayRecommendations($driver);
        } else {
            $this->error('❌ Application Verification Status: <error>Failed</error>');
            $this->displayFailureDetails($driver);
        }
    }

    /**
     * Check if Nadi is enabled
     */
    private function checkEnabled(bool $isEnabled): void
    {
        if ($isEnabled) {
            $this->line('✅ Nadi monitoring: <info>Enabled</info>');
        } else {
            $this->line('⚠️  Nadi monitoring: <comment>Disabled</comment>');
            $this->warn('   Set NADI_ENABLED=true to enable monitoring');
        }
    }

    /**
     * Check driver configuration
     */
    private function checkDriverConfiguration(string $driver): void
    {
        $this->line("📋 Driver: <comment>{$driver}</comment>");

        switch ($driver) {
            case 'opentelemetry':
                $this->checkOpenTelemetryConfig();
                break;

            case 'http':
                $this->checkHttpConfig();
                break;

            case 'log':
                $this->checkLogConfig();
                break;

            default:
                $this->error("   ❌ Unknown driver: {$driver}");
        }
    }

    /**
     * Check OpenTelemetry configuration
     */
    private function checkOpenTelemetryConfig(): void
    {
        $endpoint = config('nadi.connections.opentelemetry.endpoint');
        $serviceName = config('nadi.connections.opentelemetry.service_name');
        $serviceVersion = config('nadi.connections.opentelemetry.service_version');

        $this->line("   📡 Endpoint: <comment>{$endpoint}</comment>");
        $this->line("   🏷️  Service: <comment>{$serviceName}</comment> v<comment>{$serviceVersion}</comment>");

        // Check if endpoint is reachable (basic check)
        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $this->line('   ✅ Endpoint URL format is valid');
        } else {
            $this->line('   ❌ Invalid endpoint URL format');
        }

        // Check trace sampling configuration
        $samplingRatio = config('nadi.connections.opentelemetry.trace_sampling.ratio', 1.0);
        $this->line("   📊 Trace sampling ratio: <comment>{$samplingRatio}</comment>");
    }

    /**
     * Check HTTP configuration
     */
    private function checkHttpConfig(): void
    {
        $endpoint = config('nadi.connections.http.endpoint');
        $apiKey = config('nadi.connections.http.apiKey');
        $appKey = config('nadi.connections.http.appKey');

        $this->line("   🌐 Endpoint: <comment>{$endpoint}</comment>");
        $this->line('   🔑 API Key: '.($apiKey ? '<info>Set</info>' : '<error>Missing</error>'));
        $this->line('   🎫 App Key: '.($appKey ? '<info>Set</info>' : '<error>Missing</error>'));

        if (! $apiKey || ! $appKey) {
            $this->warn('   Set NADI_API_KEY and NADI_APP_KEY environment variables');
        }
    }

    /**
     * Check log configuration
     */
    private function checkLogConfig(): void
    {
        $path = config('nadi.connections.log.path');
        $this->line("   📁 Storage path: <comment>{$path}</comment>");

        if (is_dir($path)) {
            $this->line('   ✅ Directory exists');

            if (is_writable($path)) {
                $this->line('   ✅ Directory is writable');
            } else {
                $this->line('   ❌ Directory is not writable');
            }
        } else {
            $this->line('   ⚠️  Directory does not exist (will be created)');
        }
    }

    /**
     * Check sampling configuration
     */
    private function checkSamplingConfiguration(): void
    {
        $strategy = config('nadi.sampling.strategy');
        $rate = config('nadi.sampling.config.sampling_rate');

        $this->line("📊 Sampling strategy: <comment>{$strategy}</comment>");
        $this->line("   📈 Sampling rate: <comment>{$rate}</comment> (".($rate * 100).'%)');
    }

    /**
     * Display recommendations for successful verification
     */
    private function displayRecommendations(string $driver): void
    {
        $this->newLine();
        $this->line('<info>💡 Recommendations:</info>');

        if ($driver === 'opentelemetry') {
            $this->line('• Consider setting up distributed tracing with OpenTelemetry');
            $this->line('• Monitor your OTLP collector health and performance');
            $this->line('• Use structured logging with trace correlation');
        }

        $this->line('• Monitor your application performance and error rates');
        $this->line('• Set up alerts based on your monitoring data');
        $this->line('• Review sampling rates to balance observability and performance');
    }

    /**
     * Display failure details
     */
    private function displayFailureDetails(string $driver): void
    {
        $this->newLine();
        $this->line('<error>🔧 Troubleshooting:</error>');

        switch ($driver) {
            case 'opentelemetry':
                $this->line('• Verify OpenTelemetry collector is running and accessible');
                $this->line('• Check network connectivity and firewall settings');
                $this->line('• Validate OTLP endpoint configuration');
                break;

            case 'http':
                $this->line('• Verify API credentials are correct');
                $this->line('• Check network connectivity to Nadi service');
                $this->line('• Ensure endpoint URL is accessible');
                break;

            case 'log':
                $this->line('• Check directory permissions');
                $this->line('• Verify filesystem has sufficient space');
                $this->line('• Ensure PHP has write access to the log directory');
                break;
        }
    }
}
