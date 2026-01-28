# Quick Start

Verify your Nadi installation and test the connection.

## Verify Installation

After running `php artisan nadi:install`, verify your setup:

```bash
php artisan nadi:verify
```

This validates:

- Enabled status
- Driver configuration
- Sampling settings
- Directory permissions
- API credentials

## Test Connectivity

Test the connection to your configured driver:

```bash
php artisan nadi:test
```

## Choose Your Driver

Nadi supports two primary workflows:

### Log Driver with Shipper (Recommended)

Best for production. Writes to local files, shipper forwards to API asynchronously.

```env
NADI_ENABLED=true
NADI_DRIVER=log
NADI_STORAGE_PATH=storage/nadi
```

Ensure the shipper is running via Supervisord (see [Installation](01-installation.md#shipper-setup)).

### HTTP Driver (Direct)

Sends data directly to the Nadi API. Simpler setup but adds latency to requests.

```env
NADI_ENABLED=true
NADI_DRIVER=http
NADI_API_KEY=your-sanctum-token
NADI_APP_KEY=your-application-key
```

## What Gets Monitored

Once configured, Nadi automatically monitors:

| Event                | What's Captured                                       |
| -------------------- | ----------------------------------------------------- |
| Exceptions           | Caught exceptions with stack traces and code context  |
| Slow Queries         | Database queries exceeding threshold (default: 500ms) |
| Failed Jobs          | Queue job failures with exception details             |
| HTTP Errors          | Requests returning 4xx/5xx status codes               |
| Failed Notifications | Notification delivery failures                        |
| Artisan Commands     | Completed console commands                            |

## Next Steps

- [Environment Variables](../03-configuration/01-environment-variables.md) - All configuration options
- [Transport Drivers](../03-configuration/02-transport-drivers.md) - HTTP, Log, OpenTelemetry setup
- [Sampling Strategies](../03-configuration/03-sampling-strategies.md) - Control data collection rate
