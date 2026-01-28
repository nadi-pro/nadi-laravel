# Quick Start

Configure your Nadi credentials and verify the connection.

## Configure Environment Variables

Add your Nadi credentials to `.env`:

```env
NADI_ENABLED=true
NADI_DRIVER=http
NADI_API_KEY=your-sanctum-token
NADI_APP_KEY=your-application-key
```

## Verify Configuration

Run the verify command to check your setup:

```bash
php artisan nadi:verify
```

This validates:

- Enabled status
- Driver configuration
- Sampling settings
- Directory permissions

## Test Connectivity

Test the connection to your configured driver:

```bash
php artisan nadi:test
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

- [Configuration Reference](../03-configuration/01-environment-variables.md) - All configuration options
- [Transport Drivers](../03-configuration/02-transport-drivers.md) - HTTP, Log, OpenTelemetry setup
