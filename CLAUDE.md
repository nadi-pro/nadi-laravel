# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nadi Laravel is a Laravel package that provides automated monitoring and observability for Laravel applications. It captures exceptions, failed jobs, slow database queries, HTTP requests, and other events, then sends them to the Nadi observability platform using multiple transport methods (HTTP, local logging, or OpenTelemetry).

**Package:** nadi-pro/nadi-laravel
**PHP:** 8.1-8.4
**Laravel:** 9.x-12.x

## Commands

```bash
composer test      # Run PHPUnit tests (Orchestra Testbench)
composer format    # Run Laravel Pint code formatting
```

Artisan commands:
- `php artisan nadi:install` - Publishes config and installs shipper binary
- `php artisan nadi:test` - Tests connectivity to configured driver
- `php artisan nadi:verify` - Validates configuration and setup

## Architecture

### Event-Driven Flow

```
Laravel Events → NadiServiceProvider Listeners → Event Handlers → Transporter → Driver (HTTP/Log/OpenTelemetry)
```

Event-to-handler mappings are configured in `config/nadi.php`:

```php
'observe' => [
    MessageLogged::class => [HandleExceptionEvent::class],
    QueryExecuted::class => [HandleQueryExecutedEvent::class],
    JobFailed::class => [HandleFailedJobEvent::class],
    // ...
]
```

### Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| Service Provider | `src/NadiServiceProvider.php` | Bootstraps package, registers event listeners dynamically |
| Transporter | `src/Transporter.php` | Wraps core PHP SDK, configures drivers and sampling |
| Event Handlers | `src/Handler/` | Capture Laravel events and create monitoring entries |
| Entry Classes | `src/Data/` | Laravel-specific entry formats with OTEL attributes |
| Semantic Conventions | `src/Support/OpenTelemetrySemanticConventions.php` | OTEL attribute constants |

### Handler Pattern

All handlers extend `Handler\Base`:

```php
public function handle(SomeEvent $event): void {
    if ($this->shouldIgnore($event)) return;

    $this->store(
        Entry::make(Type::EXCEPTION, $data)
            ->setHashFamily($this->hash($uniqueId))
            ->tags($this->tags($event))
            ->toArray()
    );
}
```

### Environment Variables

- `NADI_ENABLED` - Master toggle
- `NADI_DRIVER` - Transport: log, http, opentelemetry
- `NADI_API_KEY` - Sanctum token (HTTP driver)
- `NADI_APP_KEY` - Application identifier
- `NADI_ENDPOINT` - API endpoint
- `NADI_QUERY_SLOW_THRESHOLD` - Slow query threshold in ms (default: 500)
- `NADI_OTEL_*` - OpenTelemetry configuration

## Implementation Notes

- **Event listeners registered dynamically** at runtime based on config, not hardcoded in service provider
- **Auto-flush on destruct** - `Transporter::__destruct()` ensures buffered entries are sent
- **Hash families** group similar issues by day: `hash(class + file + line + message + date)`
- **Query binding replacement** - `HandleQueryExecutedEvent` properly replaces placeholders with actual values
- **Success codes filtered** - HTTP 2xx/3xx responses are ignored by default

## Adding a New Event Handler

1. Create `src/Handler/HandleYourEvent.php` extending `Base`
2. Implement `handle(YourEvent $event): void` with shouldIgnore logic
3. Add mapping to `config/nadi.php` observe array
4. Add tests in `tests/Features/`

## Dependencies

Core SDK: `nadi-pro/nadi-php` (provides entry types, sampling strategies, transport contracts)
