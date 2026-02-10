# Transport Drivers

Nadi Laravel supports three transport methods for sending monitoring data.

## Log Driver

Writes monitoring data to local JSON files. Useful for development or when using the shipper binary.

```env
NADI_DRIVER=log
NADI_STORAGE_PATH=/path/to/storage/nadi
```

The shipper binary monitors this directory and forwards files to the Nadi API.

## HTTP Driver

Sends data directly to the Nadi API over HTTP.

```env
NADI_DRIVER=http
NADI_API_KEY=your-sanctum-token
NADI_APP_KEY=your-application-key
NADI_ENDPOINT=https://nadi.pro/api
```

| Header                  | Source         | Purpose                |
| ----------------------- | -------------- | ---------------------- |
| `Authorization: Bearer` | `NADI_API_KEY` | Sanctum authentication |
| `Nadi-App-Token`        | `NADI_APP_KEY` | Application identifier |

## OpenTelemetry Driver

Exports data using OpenTelemetry Protocol (OTLP) for integration with observability platforms.

```env
NADI_DRIVER=opentelemetry
NADI_OTEL_ENDPOINT=http://localhost:4318
NADI_OTEL_SERVICE_NAME=my-app
NADI_OTEL_SERVICE_VERSION=1.0.0
NADI_OTEL_DEPLOYMENT_ENVIRONMENT=production
```

### Protocol Options

```env
# HTTP with Protocol Buffers (default, most efficient)
NADI_OTEL_PROTOCOL=http/protobuf

# gRPC (requires grpc PHP extension)
NADI_OTEL_PROTOCOL=grpc

# HTTP with JSON (human-readable, larger payloads)
NADI_OTEL_PROTOCOL=http/json
```

### Auto-Instrumentation

Enable automatic middleware registration for trace context propagation:

```env
NADI_OTEL_AUTO_INSTRUMENT_WEB=true
NADI_OTEL_AUTO_INSTRUMENT_API=true
```

Or manually apply the middleware:

```php
// routes/web.php
Route::middleware(['nadi.otel'])->group(function () {
    // Routes with OpenTelemetry instrumentation
});
```

### Span Limits

Control resource usage with span limits:

```env
NADI_OTEL_MAX_ATTRIBUTES_PER_SPAN=128
NADI_OTEL_MAX_EVENTS_PER_SPAN=128
NADI_OTEL_MAX_LINKS_PER_SPAN=128
NADI_OTEL_MAX_ATTRIBUTE_VALUE_LENGTH=4096
```

## Next Steps

- [Sampling Strategies](03-sampling-strategies.md) - Control data collection rate
- [OpenTelemetry Integration](../05-advanced/01-opentelemetry.md) - Advanced OTEL configuration
