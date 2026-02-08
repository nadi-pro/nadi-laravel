<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Nadi\Laravel\Shipper\Shipper;
use Nadi\Shipper\Exceptions\ShipperException;
use Nadi\Shipper\Exceptions\UnsupportedPlatformException;

class InstallCommand extends Command
{
    /**
     * The reference YAML URL from GitHub.
     */
    private const REFERENCE_YAML_URL = 'https://raw.githubusercontent.com/nadi-pro/shipper/refs/heads/master/nadi.reference.yaml';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:install
                            {--force : Force overwrite existing config files}
                            {--skip-shipper : Skip shipper binary installation}
                            {--skip-config : Skip shipper config (nadi.yaml) setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Nadi for Laravel';

    public function handle(): int
    {
        $this->publishConfig();

        if (! $this->option('skip-shipper')) {
            $this->installShipper();
        }

        if (! $this->option('skip-config')) {
            $this->setupShipperConfig();
        }

        $this->newLine();
        $this->info('Successfully installed Nadi');

        return self::SUCCESS;
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

    /**
     * Setup the shipper configuration file (nadi.yaml).
     */
    private function setupShipperConfig(): void
    {
        $this->newLine();
        $this->info('Setting up Nadi Shipper configuration...');

        // Create storage directory
        $storagePath = $this->getStoragePath();
        $this->createStorageDirectory($storagePath);

        // Check if config already exists
        $configPath = $storagePath.'/nadi.yaml';
        if (File::exists($configPath) && ! $this->option('force')) {
            $this->warn("Config file already exists: {$configPath}");
            $this->warn('Use --force to overwrite');

            return;
        }

        // Download reference YAML from GitHub
        $yamlContent = $this->downloadReferenceYaml();
        if ($yamlContent === null) {
            return;
        }

        // Ask for credentials
        $credentials = $this->askForCredentials();

        // Update .env file with credentials
        $this->updateEnvFile($credentials);

        // Replace placeholders in YAML
        $yamlContent = $this->configureYaml($yamlContent, $credentials, $storagePath);

        // Save the config file
        File::put($configPath, $yamlContent);
        $this->info("Created config: {$configPath}");

        // Show credential reminder if skipped
        if (empty($credentials['apiKey']) || empty($credentials['appKey'])) {
            $this->newLine();
            $this->warn('API credentials not configured.');
            $this->line('Create your API Key at: <comment>https://nadi.pro/user/api-tokens</comment>');
            $this->line('Find your App Key in your application page at: <comment>https://nadi.pro</comment>');
            $this->line("Then update your <comment>.env</comment> file and <comment>{$configPath}</comment>");
        }

        // Create supervisord config
        $this->createSupervisordConfig();
    }

    /**
     * Get the storage path for Nadi logs.
     */
    private function getStoragePath(): string
    {
        return rtrim(config('nadi.connections.log.path', storage_path('nadi')), '/');
    }

    /**
     * Create the storage directory if it doesn't exist.
     */
    private function createStorageDirectory(string $path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
            $this->line("Created directory: <info>{$path}</info>");
        }

        // Create .gitignore to exclude log files but keep config
        $gitignorePath = $path.'/.gitignore';
        if (! File::exists($gitignorePath)) {
            File::put($gitignorePath, "*\n!.gitignore\n!nadi.yaml\n!dead-letter\n");
        }

        // Create dead-letter directory
        $deadLetterPath = $path.'/dead-letter';
        if (! File::isDirectory($deadLetterPath)) {
            File::makeDirectory($deadLetterPath, 0755, true);
            $this->line("Created directory: <info>{$deadLetterPath}</info>");
        }

        // Create .gitignore in dead-letter directory
        $deadLetterGitignore = $deadLetterPath.'/.gitignore';
        if (! File::exists($deadLetterGitignore)) {
            File::put($deadLetterGitignore, "*\n!.gitignore\n");
        }
    }

    /**
     * Download the reference YAML from GitHub.
     */
    private function downloadReferenceYaml(): ?string
    {
        $this->line('Downloading reference configuration from GitHub...');

        try {
            $response = Http::timeout(30)->get(self::REFERENCE_YAML_URL);

            if ($response->successful()) {
                $this->line('Reference configuration downloaded successfully');

                return $response->body();
            }

            $this->error('Failed to download reference configuration: HTTP '.$response->status());
            $this->warn('You can manually download from: '.self::REFERENCE_YAML_URL);

            return null;
        } catch (\Exception $e) {
            $this->error('Failed to download reference configuration: '.$e->getMessage());
            $this->warn('You can manually download from: '.self::REFERENCE_YAML_URL);

            return null;
        }
    }

    /**
     * Ask user for API credentials.
     */
    private function askForCredentials(): array
    {
        $this->newLine();
        $this->line('<comment>Configure API credentials</comment>');
        $this->line('Create your API Key at: <info>https://nadi.pro/user/api-tokens</info>');
        $this->line('Find your App Key in your application page at: <info>https://nadi.pro</info>');
        $this->line('Press Enter to skip and configure later.');
        $this->newLine();

        $apiKey = $this->ask('API Key (from https://nadi.pro/user/api-tokens)');
        $appKey = $this->ask('App Key (from your application page)');

        return [
            'apiKey' => $apiKey,
            'appKey' => $appKey,
        ];
    }

    /**
     * Update the .env file with Nadi credentials.
     */
    private function updateEnvFile(array $credentials): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        $keys = [
            'NADI_API_KEY' => $credentials['apiKey'] ?? '',
            'NADI_APP_KEY' => $credentials['appKey'] ?? '',
        ];

        foreach ($keys as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);

        $this->line('Updated <info>.env</info> with NADI_API_KEY and NADI_APP_KEY');
    }

    /**
     * Configure the YAML content with actual values.
     */
    private function configureYaml(string $yaml, array $credentials, string $storagePath): string
    {
        $endpoint = config('nadi.connections.http.endpoint', 'https://api.nadi.pro');

        // Replace endpoint
        $yaml = preg_replace(
            '/^(\s*endpoint:\s*).*$/m',
            '${1}'.$endpoint,
            $yaml
        );

        // Replace apiKey
        if (! empty($credentials['apiKey'])) {
            $yaml = preg_replace(
                '/^(\s*apiKey:\s*).*$/m',
                '${1}'.$credentials['apiKey'],
                $yaml
            );
        }

        // Replace appKey
        if (! empty($credentials['appKey'])) {
            $yaml = preg_replace(
                '/^(\s*appKey:\s*).*$/m',
                '${1}'.$credentials['appKey'],
                $yaml
            );
        }

        // Replace storage path
        $yaml = preg_replace(
            '/^(\s*storage:\s*).*$/m',
            '${1}'.$storagePath,
            $yaml
        );

        return $yaml;
    }

    /**
     * Create supervisord configuration file.
     */
    private function createSupervisordConfig(): void
    {
        $shipper = new Shipper;
        $binaryPath = $shipper->getBinaryPath();
        $storagePath = $this->getStoragePath();
        $configPath = $storagePath.'/nadi.yaml';
        $projectPath = base_path();
        $appName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', config('app.name', 'laravel')));

        $user = get_current_user();

        $supervisorConfig = <<<CONF
[program:nadi-shipper-{$appName}]
command={$binaryPath} --config="{$configPath}" --record
directory=/
redirect_stderr=true
autostart=true
autorestart=true
user={$user}
numprocs=1
process_name=%(program_name)s_%(process_num)s
CONF;

        $fileName = "nadi-shipper-{$appName}.conf";
        $supervisordDir = '/etc/supervisor/conf.d';

        $this->newLine();

        if (File::isDirectory($supervisordDir)) {
            $targetPath = $supervisordDir.'/'.$fileName;
            File::put($targetPath, $supervisorConfig);
            $this->info("Supervisord config created: {$targetPath}");
        } else {
            $targetPath = $storagePath.'/'.$fileName;
            File::put($targetPath, $supervisorConfig);
            $this->info("Supervisord config created: {$targetPath}");
            $this->warn("Supervisord directory ({$supervisordDir}) not found.");
            $this->line('Copy the config when supervisord is available:');
            $this->line("<comment>sudo cp {$targetPath} {$supervisordDir}/{$fileName}</comment>");
        }

        $this->newLine();
        $this->line('Then run:');
        $this->line('<comment>sudo supervisorctl reread</comment>');
        $this->line('<comment>sudo supervisorctl update</comment>');
        $this->line("<comment>sudo supervisorctl start nadi-shipper-{$appName}</comment>");

        $this->newLine();
        $this->line('Check status:');
        $this->line("<comment>sudo supervisorctl status nadi-shipper-{$appName}</comment>");
    }
}
