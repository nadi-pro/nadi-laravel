# Architecture Overview

Nadi Laravel uses an event-driven architecture to capture and transmit monitoring data.

## Event Flow

```text
Laravel Events → NadiServiceProvider Listeners → Event Handlers → Transporter → Driver
```

1. **Laravel emits events** - Exceptions logged, queries executed, jobs failed, etc.
2. **Service provider registers listeners** - Dynamically from `config/nadi.php`
3. **Handlers process events** - Extract relevant data, apply filtering
4. **Transporter buffers entries** - Collects data for batch transmission
5. **Driver sends data** - HTTP, local log, or OpenTelemetry

## Event-to-Handler Mapping

The `observe` configuration maps Laravel events to handler classes:

```php
// config/nadi.php
'observe' => [
    MessageLogged::class => [HandleExceptionEvent::class],
    QueryExecuted::class => [HandleQueryExecutedEvent::class],
    JobFailed::class => [HandleFailedJobEvent::class],
    RequestHandled::class => [HandleHttpRequestEvent::class],
    NotificationFailed::class => [HandleNotificationFailedEvent::class],
    CommandFinished::class => [HandleCommandEvent::class],
]
```

This dynamic registration allows you to:

- Add custom handlers for existing events
- Remove handlers you don't need
- Create handlers for additional Laravel events

## Data Structure

Each monitored entry includes:

- **Base fields**: file, line, message, context, trace
- **OpenTelemetry attributes**: Semantic convention data for observability platforms
- **Tags**: Searchable labels (e.g., `slow`, `laravel.route.name:home`)
- **Hash family**: Groups similar issues by day for deduplication

## Next Steps

- [Core Components](02-core-components.md) - Detailed component breakdown
