<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Nadi\Laravel\Shipper\Shipper;
use Nadi\Shipper\Exceptions\ShipperException;
use Nadi\Shipper\Exceptions\UnsupportedPlatformException;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:install
                            {--force : Force overwrite existing config file}
                            {--skip-shipper : Skip shipper binary installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Nadi for Laravel';

    public function handle()
    {
        $this->publishConfig();

        if (! $this->option('skip-shipper')) {
            $this->installShipper();
        }

        $this->info('Successfully installed Nadi');
    }

    /**
     * Publish the Nadi configuration file.
     */
    private function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'nadi-config',
            '--force' => $this->option('force') ?? false,
        ]);
    }

    /**
     * Install the shipper binary.
     */
    private function installShipper(): void
    {
        $this->info('Installing shipper binary...');

        try {
            $shipper = new Shipper;

            if ($shipper->isInstalled()) {
                $version = $shipper->getInstalledVersion() ?? 'unknown';
                $this->info("Shipper binary already installed (version: {$version})");
                $this->info("Binary location: {$shipper->getBinaryPath()}");

                return;
            }

            $binaryPath = $shipper->install();

            $version = $shipper->getInstalledVersion() ?? 'unknown';
            $this->info("Shipper binary installed successfully (version: {$version})");
            $this->info("Binary location: {$binaryPath}");
        } catch (UnsupportedPlatformException $e) {
            $this->warn('Shipper binary installation skipped: '.$e->getMessage());
            $this->warn('You can install the shipper binary manually from: https://github.com/nadi-pro/shipper/releases');
        } catch (ShipperException $e) {
            $this->error('Failed to install shipper binary: '.$e->getMessage());
            $this->warn('You can install the shipper binary manually from: https://github.com/nadi-pro/shipper/releases');
        }
    }
}
