# Installation

Install Nadi Laravel to enable automated monitoring in your Laravel application.

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher

## Install via Composer

```bash
composer require nadi-pro/nadi-laravel
```

## Run the Install Command

```bash
php artisan nadi:install
```

This command performs the following steps:

1. Publishes the `nadi.php` configuration file to your `config/` directory
2. Downloads and installs the Nadi Shipper binary to `vendor/bin/`
3. Creates the `storage/nadi/` directory for log files
4. Downloads the latest `nadi.yaml` configuration from GitHub
5. Prompts for your API credentials (can be skipped)
6. Displays Supervisord setup instructions

### Interactive Credential Setup

During installation, you'll be prompted to enter:

- **API Key** - Your Sanctum token from your Nadi account
- **App Key** - Your application identifier from Nadi

Press Enter to skip and configure later. Get your credentials at [https://nadi.pro](https://nadi.pro).

### Installation Options

Skip shipper binary installation:

```bash
php artisan nadi:install --skip-shipper
```

Skip shipper config (nadi.yaml) setup:

```bash
php artisan nadi:install --skip-config
```

Force overwrite existing configuration files:

```bash
php artisan nadi:install --force
```

## Shipper Setup

The shipper binary monitors `storage/nadi/` for log files and forwards them to the Nadi API.
After installation, set up Supervisord to run the shipper as a background process.

### Supervisord Configuration

Create a supervisor config file:

```bash
sudo nano /etc/supervisor/conf.d/nadi-shipper.conf
```

Add the following configuration (paths are shown during installation):

```ini
[program:nadi-shipper-your-app]
process_name=%(program_name)s
command=/path/to/project/vendor/bin/shipper --config=/path/to/project/storage/nadi/nadi.yaml
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/shipper.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=3600
```

Apply the configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nadi-shipper-your-app
```

Check status:

```bash
sudo supervisorctl status nadi-shipper-your-app
```

## Manual Credential Configuration

If you skipped credentials during installation, update `storage/nadi/nadi.yaml`:

```yaml
nadi:
  apiKey: your-api-key-here
  token: your-app-key-here
```

Or set environment variables for the Laravel HTTP driver:

```env
NADI_API_KEY=your-api-key
NADI_APP_KEY=your-app-key
```

## Next Steps

- [Quick Start](02-quick-start.md) - Verify setup and test connectivity
