# Exception Handler

Captures exceptions logged via Laravel's logging system.

## Event

Listens to: `Illuminate\Log\Events\MessageLogged`

## What's Captured

- Exception class name
- Error message
- File and line number
- Full stack trace
- Code context (10 lines before/after error line)
- Request context (if available)

## Code Context

The `ExceptionContext::get()` action extracts surrounding code:

```php
// If error occurs at line 50
// Returns lines 40-60 for debugging context
```

## Tag Extraction

Tags are extracted from exception properties using reflection:

```php
// If exception has public $userId property
// Tag: 'userId:123'
```

## Filtering

Exceptions are filtered based on:

- Log level (only errors and above)
- Exception type (only throwable instances)

## Configuration

No specific configuration required. Enabled by default in the `observe` mapping.

## Next Steps

- [Query Handler](03-queries.md) - Slow query monitoring
