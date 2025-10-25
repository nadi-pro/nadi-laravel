<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connectivity to Nadi API';

    public function handle()
    {
        $driver = config('nadi.driver');
        $this->info("Testing Nadi connectivity using driver: <comment>{$driver}</comment>");

        $isActive = app('nadi')->test();

        if ($isActive) {
            $this->info('✅ Connectivity to Nadi is: <info>Active</info>');

            // Provide driver-specific information
            $this->displayDriverInfo($driver);
        } else {
            $this->error('❌ Connectivity to Nadi is: <error>Inactive</error>');

            // Provide troubleshooting information
            $this->displayTroubleshootingInfo($driver);
        }
    }

    /**
     * Display driver-specific information
     */
    private function displayDriverInfo(string $driver): void
    {
        switch ($driver) {
            case 'opentelemetry':
                $endpoint = config('nadi.connections.opentelemetry.endpoint');
                $serviceName = config('nadi.connections.opentelemetry.service_name');
                $this->line("📡 OpenTelemetry endpoint: <comment>{$endpoint}</comment>");
                $this->line("🏷️  Service name: <comment>{$serviceName}</comment>");
                break;

            case 'http':
                $endpoint = config('nadi.connections.http.endpoint');
                $this->line("🌐 HTTP endpoint: <comment>{$endpoint}</comment>");
                break;

            case 'log':
                $path = config('nadi.connections.log.path');
                $this->line("📁 Log storage path: <comment>{$path}</comment>");
                break;
        }
    }

    /**
     * Display troubleshooting information
     */
    private function displayTroubleshootingInfo(string $driver): void
    {
        $this->newLine();
        $this->warn('Troubleshooting tips:');

        switch ($driver) {
            case 'opentelemetry':
                $this->line('• Check if your OpenTelemetry collector is running');
                $this->line('• Verify the endpoint URL: '.config('nadi.connections.opentelemetry.endpoint'));
                $this->line('• Ensure network connectivity to the OTLP endpoint');
                $this->line('• Check if the collector accepts HTTP/protobuf format');
                break;

            case 'http':
                $this->line('• Verify your API credentials (NADI_KEY and NADI_TOKEN)');
                $this->line('• Check the endpoint URL: '.config('nadi.connections.http.endpoint'));
                $this->line('• Ensure network connectivity to the Nadi service');
                break;

            case 'log':
                $path = config('nadi.connections.log.path');
                $this->line("• Check if the directory is writable: {$path}");
                $this->line('• Verify filesystem permissions');
                break;
        }
    }
}
