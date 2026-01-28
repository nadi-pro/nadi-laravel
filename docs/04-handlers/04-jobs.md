# Job Handler

Tracks failed queue jobs.

## Event

Listens to: `Illuminate\Queue\Events\JobFailed`

## What's Captured

- Job class name
- Queue name
- Connection name
- Exception details
- Job payload properties
- Attempt count

## Property Extraction

Job properties are extracted for context:

```php
class SendEmail extends Job
{
    public $userId;    // Captured
    public $email;     // Captured
    protected $secret; // Not captured (protected)
}
```

## Tag Extraction

Tags are extracted from:

- Exception properties (if any)
- Job public properties

## Model Information

If job properties contain Eloquent models, model info is extracted:

```php
// Tag: 'App\Models\User:123'
```

## Configuration

No specific configuration required. Enabled by default in the `observe` mapping.

## Next Steps

- [HTTP Handler](05-http.md) - HTTP error monitoring
