<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Nadi\Laravel\Shipper\Shipper;
use Nadi\Shipper\Exceptions\ShipperException;
use Nadi\Shipper\Exceptions\UnsupportedPlatformException;

class UpdateShipperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:update-shipper
                            {--force : Force replace the shipper binary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the Nadi shipper binary';

    public function handle(): int
    {
        try {
            $shipper = new Shipper;

            if ($this->option('force')) {
                return $this->forceReInstall($shipper);
            }

            return $this->updateWithCheck($shipper);
        } catch (UnsupportedPlatformException $e) {
            $this->error('Unsupported platform: '.$e->getMessage());
            $this->warn('You can install the shipper binary manually from: https://github.com/nadi-pro/shipper/releases');

            return self::FAILURE;
        } catch (ShipperException $e) {
            $this->error('Failed to update shipper binary: '.$e->getMessage());
            $this->warn('You can install the shipper binary manually from: https://github.com/nadi-pro/shipper/releases');

            return self::FAILURE;
        }
    }

    /**
     * Force re-install the shipper binary, skipping version checks.
     */
    private function forceReInstall(Shipper $shipper): int
    {
        $this->info('Force re-installing shipper binary...');

        $version = $shipper->reInstall();

        $this->info("Shipper binary installed successfully (version: {$version})");
        $this->info("Binary location: {$shipper->getBinaryPath()}");

        return self::SUCCESS;
    }

    /**
     * Check for updates and prompt the user before updating.
     */
    private function updateWithCheck(Shipper $shipper): int
    {
        if (! $shipper->isInstalled()) {
            $this->warn('Shipper binary is not installed.');
            $this->line('Run <comment>php artisan nadi:install</comment> to install it.');

            return self::FAILURE;
        }

        $installedVersion = $shipper->getInstalledVersion() ?? 'unknown';
        $latestVersion = $shipper->getLatestVersion();

        $installedNormalized = ltrim($installedVersion, 'v');
        $latestNormalized = ltrim($latestVersion, 'v');

        if (version_compare($installedNormalized, $latestNormalized, '>=')) {
            $this->info("Shipper binary is already up to date (version: {$installedVersion})");

            return self::SUCCESS;
        }

        $this->info("Current version: {$installedVersion}");
        $this->info("Latest version:  {$latestVersion}");

        if (! $this->confirm('Do you want to update the shipper binary?')) {
            $this->info('Update cancelled.');

            return self::SUCCESS;
        }

        $this->info('Updating shipper binary...');

        $newVersion = $shipper->update();

        $this->info("Shipper binary updated successfully (version: {$newVersion})");
        $this->info("Binary location: {$shipper->getBinaryPath()}");

        return self::SUCCESS;
    }
}
