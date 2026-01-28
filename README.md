<p align="center">
<a href="https://github.com/nadi-pro/nadi-laravel/actions"><img src="https://github.com/nadi-pro/nadi-laravel/actions/workflows/run-tests.yml/badge.svg" alt="Build Status"></a>
</p>

# Nadi Laravel Client

Nadi is a simple issue tracker for monitoring your application crashes. This package developed for Laravel framework.

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher

## Installation

```bash
composer require nadi-pro/nadi-laravel
```

Then run the install command:

```bash
php artisan nadi:install
```

This will:

1. Publish the `nadi.php` configuration file to your `config/` directory
2. Automatically download and install the Nadi Shipper binary to `vendor/bin/`

### Skip Shipper Installation

If you don't want to install the shipper binary automatically (e.g., you're using the HTTP transporter only), use the `--skip-shipper` flag:

```bash
php artisan nadi:install --skip-shipper
```

## Configuration

After installation, configure your Nadi credentials in `config/nadi.php` or via environment variables:

```env
NADI_API_KEY=your-sanctum-token
NADI_APP_KEY=your-application-key
```

## Shipper Binary

The Nadi Shipper is a lightweight Go binary that monitors a directory for JSON log files and forwards them to the Nadi API. It's automatically installed during `php artisan nadi:install`.

### Binary Location

The shipper binary is installed at:

```text
vendor/bin/shipper
```

### Manual Shipper Management

You can also manage the shipper binary programmatically:

```php
use Nadi\Laravel\Shipper\Shipper;

$shipper = new Shipper();

// Check if installed
if ($shipper->isInstalled()) {
    echo "Version: " . $shipper->getInstalledVersion();
    echo "Path: " . $shipper->getBinaryPath();
}

// Install or update
$shipper->install();

// Check for updates
if ($shipper->needsUpdate()) {
    $shipper->update();
}

// Execute shipper commands
$result = $shipper->send(config_path('nadi.yaml'));
$result = $shipper->test(config_path('nadi.yaml'));
$result = $shipper->verify(config_path('nadi.yaml'));

// Uninstall
$shipper->uninstall();
```

### Supported Platforms

| Operating System | Architectures |
|-----------------|---------------|
| Linux | amd64, 386, arm64 |
| macOS (Darwin) | amd64, arm64 |
| Windows | amd64 |

## Available Commands

```bash
# Install Nadi (publishes config + installs shipper)
php artisan nadi:install

# Install without shipper binary
php artisan nadi:install --skip-shipper

# Force overwrite existing config
php artisan nadi:install --force

# Test API connectivity
php artisan nadi:test

# Verify configuration
php artisan nadi:verify
```

## Documentation

Refer to [documentation](https://docs.nadi.pro) for detailed installation and usage instructions.
