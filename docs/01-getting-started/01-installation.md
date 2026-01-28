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

This command:

1. Publishes the `nadi.php` configuration file to your `config/` directory
2. Downloads and installs the Nadi Shipper binary to `vendor/bin/`

### Skip Shipper Installation

If you're using the HTTP transporter only and don't need the shipper binary:

```bash
php artisan nadi:install --skip-shipper
```

### Force Overwrite Config

To overwrite an existing configuration file:

```bash
php artisan nadi:install --force
```

## Next Steps

- [Quick Start](02-quick-start.md) - Configure credentials and verify setup
