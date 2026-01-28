# Custom Handlers

Create handlers for additional Laravel events.

## Creating a Handler

### 1. Create the Handler Class

```php
<?php

namespace App\Handlers;

use Nadi\Data\Type;
use Nadi\Laravel\Data\Entry;
use Nadi\Laravel\Handler\Base;
use App\Events\PaymentProcessed;

class HandlePaymentEvent extends Base
{
    public function handle(PaymentProcessed $event): void
    {
        // Early filtering
        if ($this->shouldIgnore($event)) {
            return;
        }

        // Extract event data
        $data = [
            'message' => "Payment processed: {$event->payment->id}",
            'amount' => $event->payment->amount,
            'currency' => $event->payment->currency,
            'customer_id' => $event->payment->customer_id,
        ];

        // Store the entry
        $this->store(
            Entry::make(Type::EVENT, $data)
                ->setHashFamily($this->hash($event->payment->id . date('Y-m-d')))
                ->tags(['payment', "customer:{$event->payment->customer_id}"])
                ->toArray()
        );
    }

    protected function shouldIgnore(PaymentProcessed $event): bool
    {
        // Skip test payments
        return $event->payment->is_test;
    }
}
```

### 2. Register in Configuration

```php
// config/nadi.php
'observe' => [
    // ... existing handlers
    App\Events\PaymentProcessed::class => [
        App\Handlers\HandlePaymentEvent::class,
    ],
],
```

## Handler Base Methods

### store()

Save an entry via the transporter:

```php
$this->store($entry->toArray());
```

### hash()

Generate a hash family for grouping:

```php
$this->hash($uniqueIdentifier);
```

### tags()

Extract tags from an event (if using `ExtractTags`):

```php
$this->tags($event);
```

## Entry Types

Available entry types from `Nadi\Data\Type`:

- `Type::EXCEPTION` - Errors and exceptions
- `Type::QUERY` - Database queries
- `Type::REQUEST` - HTTP requests
- `Type::JOB` - Queue jobs
- `Type::EVENT` - Custom events
- `Type::LOG` - Log entries

## Adding OpenTelemetry Attributes

Include OTEL attributes for observability platforms:

```php
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;

$otelAttributes = [
    'payment.id' => $event->payment->id,
    'payment.amount' => $event->payment->amount,
    'payment.currency' => $event->payment->currency,
];

$data['otel'] = $otelAttributes;
```

## Multiple Handlers per Event

You can register multiple handlers for the same event:

```php
'observe' => [
    PaymentProcessed::class => [
        HandlePaymentMetrics::class,
        HandlePaymentAudit::class,
    ],
],
```

## Next Steps

- [Handler Overview](../04-handlers/01-overview.md) - Built-in handler patterns
