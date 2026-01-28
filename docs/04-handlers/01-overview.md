# Handler Overview

All event handlers extend `Nadi\Laravel\Handler\Base` and follow a consistent pattern.

## Handler Pattern

```php
class HandleSomeEvent extends Base
{
    public function handle(SomeEvent $event): void
    {
        // 1. Early filtering
        if ($this->shouldIgnore($event)) {
            return;
        }

        // 2. Extract contextual data
        $data = [
            'message' => $event->getMessage(),
            'context' => $event->getContext(),
        ];

        // 3. Generate OpenTelemetry attributes
        $otelData = OpenTelemetrySemanticConventions::someAttributes(...);

        // 4. Create and store entry
        $this->store(
            Entry::make(Type::SOMETHING, array_merge($data, ['otel' => $otelData]))
                ->setHashFamily($this->hash($identifier))
                ->tags($this->tags($event))
                ->toArray()
        );
    }
}
```

## Built-in Handlers

| Handler                         | Event                | Purpose                     |
| ------------------------------- | -------------------- | --------------------------- |
| `HandleExceptionEvent`          | `MessageLogged`      | Exceptions with stack traces|
| `HandleQueryExecutedEvent`      | `QueryExecuted`      | Slow database queries       |
| `HandleFailedJobEvent`          | `JobFailed`          | Failed queue jobs           |
| `HandleHttpRequestEvent`        | `RequestHandled`     | HTTP error responses        |
| `HandleNotificationFailedEvent` | `NotificationFailed` | Notification failures       |
| `HandleCommandEvent`            | `CommandFinished`    | Artisan command completion  |

## Hash Families

Issues are grouped by hash family for deduplication:

```php
$this->hash($class . $file . $line . $message . date('Y-m-d'))
```

This groups identical errors from the same day.

## Tags

Tags enable searchable labels on entries:

```php
$this->tags($event)  // Returns: ['laravel.route.name:home', 'slow']
```

## Next Steps

- [Exception Handler](02-exceptions.md) - Detailed exception handling
