# Nadi Laravel Package - AI Coding Agent Instructions

## Project Overview

Nadi Laravel is a Laravel-specific package that integrates with the Nadi PHP SDK to provide automated error/crash monitoring for Laravel applications. It follows an event-driven architecture using Laravel's event system to capture exceptions, slow queries, failed jobs, and other events automatically.

## Architecture

### Event-Driven Monitoring System

The package uses Laravel's event system with a centralized configuration in `config/nadi.php` that maps Laravel events to handler classes:

```php
'observe' => [
    MessageLogged::class => [HandleExceptionEvent::class],
    QueryExecuted::class => [HandleQueryExecutedEvent::class],
    JobFailed::class => [HandleFailedJobEvent::class],
    // ... more event mappings
]
```

### Handler Pattern

All event handlers extend `Nadi\Laravel\Handler\Base` and implement event-specific logic:

- **HandleExceptionEvent**: Captures exceptions from log events, extracts stack traces, and formats context
- **HandleQueryExecutedEvent**: Monitors slow database queries based on configurable thresholds
- **HandleHttpRequestEvent**: Tracks HTTP request/response data including headers and response codes

### Laravel Integration Wrapper

`Nadi\Laravel\Transporter` wraps the core PHP SDK, providing Laravel-specific configuration and service binding. The service provider automatically registers event listeners based on config and binds the transporter as a singleton.

## Development Workflows

### Testing
```bash
composer test          # Run PHPUnit tests with Orchestra Testbench
composer format         # Run Laravel Pint code formatting
```

### Console Commands

The package provides three Artisan commands:
- `nadi:install` - Publishes configuration file
- `nadi:test` - Tests connectivity and configuration
- `nadi:verify` - Verifies installation and setup

## Project-Specific Conventions

### Handler Development

When creating new event handlers:

1. Extend `Nadi\Laravel\Handler\Base`
2. Implement `handle(EventClass $event): void`
3. Use `$this->store()` to save entries
4. Include `shouldIgnore()` method for filtering logic
5. Extract tags using `ExtractTags::from()` for exceptions

### Entry Creation Pattern

Use Laravel-specific entry classes that extend the PHP SDK entries:
```php
ExceptionEntry::make($exception, Type::EXCEPTION, $data)
    ->setHashFamily($this->hash($uniqueIdentifier))
    ->tags($this->tags($event))
    ->toArray()
```

### Configuration Structure

Environment variables follow `NADI_` prefix convention:
- `NADI_ENABLED` - Toggle monitoring on/off
- `NADI_DRIVER` - Transport method (log, http)
- `NADI_QUERY_SLOW_THRESHOLD` - Database query monitoring threshold

### Laravel Version Support

The package supports Laravel 6.x through 11.x with PHP 7.4-8.4. Use Orchestra Testbench for testing across Laravel versions.

## Integration Points

### Service Provider Registration

Auto-discovery registers `NadiServiceProvider` which:
- Publishes and merges configuration
- Registers console commands
- Sets up event listeners based on config
- Binds transporter singleton

### Data Collection Integration

- **Exception Context**: Uses `ExceptionContext::get()` to capture file previews around error lines
- **Request Data**: Integrates with Laravel's request lifecycle for HTTP monitoring
- **Database Monitoring**: Hooks into Eloquent query events for performance tracking

### Cross-Package Dependencies

This package depends on `nadi-pro/nadi-php` for core functionality. Key shared concepts:
- Entry types and data structures
- Sampling strategies and configuration
- Transport layer contracts and implementations

## Critical Files

- `src/NadiServiceProvider.php` - Laravel service registration and event binding
- `config/nadi.php` - Event-to-handler mapping and configuration defaults
- `src/Handler/` - Event-specific monitoring logic
- `src/Transporter.php` - Laravel wrapper around PHP SDK transporter
- `.github/workflows/run-tests.yml` - Multi-version Laravel testing matrix
