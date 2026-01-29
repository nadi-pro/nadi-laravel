<?php

namespace Nadi\Laravel\Shipper;

use Nadi\Shipper\BinaryManager;
use Nadi\Shipper\Exceptions\ShipperException;

class Shipper
{
    private BinaryManager $manager;

    public function __construct(?string $binaryDirectory = null)
    {
        $directory = $binaryDirectory ?? base_path('vendor/bin');
        $this->manager = new BinaryManager($directory);
    }

    /**
     * Install the shipper binary.
     *
     * @param  string|null  $version  Specific version to install, or null for latest
     * @return string The path to the installed binary
     *
     * @throws ShipperException
     */
    public function install(?string $version = null): string
    {
        return $this->manager->install($version);
    }

    /**
     * Check if the shipper binary is installed.
     */
    public function isInstalled(): bool
    {
        return $this->manager->isInstalled();
    }

    /**
     * Get the full path to the shipper binary.
     */
    public function getBinaryPath(): string
    {
        return $this->manager->getBinaryPath();
    }

    /**
     * Get the binary directory path.
     */
    public function getBinaryDirectory(): string
    {
        return $this->manager->getBinaryDirectory();
    }

    /**
     * Get the currently installed version.
     */
    public function getInstalledVersion(): ?string
    {
        return $this->manager->getInstalledVersion();
    }

    /**
     * Check if an update is available.
     */
    public function needsUpdate(): bool
    {
        return $this->manager->needsUpdate();
    }

    /**
     * Update to the latest version if needed.
     *
     * @return string|null The new version if updated, null if already up to date
     *
     * @throws ShipperException
     */
    public function update(): ?string
    {
        return $this->manager->update();
    }

    /**
     * Re-install the binary regardless of current version state.
     *
     * @param  string|null  $version  Specific version to install, or null for latest
     * @return string The installed version
     *
     * @throws ShipperException
     */
    public function reInstall(?string $version = null): string
    {
        return $this->manager->reInstall($version);
    }

    /**
     * Get the latest available version from GitHub.
     *
     * @throws \Nadi\Shipper\Exceptions\DownloadException
     */
    public function getLatestVersion(): string
    {
        return $this->manager->getLatestVersion();
    }

    /**
     * Uninstall the shipper binary.
     */
    public function uninstall(): void
    {
        $this->manager->uninstall();
    }

    /**
     * Execute the shipper binary to send records.
     *
     * @param  string  $configPath  Path to the nadi.yaml config file
     * @return array{output: string, exitCode: int}
     *
     * @throws ShipperException
     */
    public function send(string $configPath): array
    {
        return $this->manager->execute([
            '--config='.$configPath,
            '--record',
        ]);
    }

    /**
     * Test API connectivity using the shipper binary.
     *
     * @param  string  $configPath  Path to the nadi.yaml config file
     * @return array{output: string, exitCode: int}
     *
     * @throws ShipperException
     */
    public function test(string $configPath): array
    {
        return $this->manager->execute([
            '--config='.$configPath,
            '--test',
        ]);
    }

    /**
     * Verify configuration using the shipper binary.
     *
     * @param  string  $configPath  Path to the nadi.yaml config file
     * @return array{output: string, exitCode: int}
     *
     * @throws ShipperException
     */
    public function verify(string $configPath): array
    {
        return $this->manager->execute([
            '--config='.$configPath,
            '--verify',
        ]);
    }

    /**
     * Get the underlying BinaryManager instance.
     */
    public function getManager(): BinaryManager
    {
        return $this->manager;
    }
}
