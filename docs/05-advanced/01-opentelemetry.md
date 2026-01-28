# OpenTelemetry Integration

Nadi Laravel supports OpenTelemetry for integration with modern observability platforms.

## Configuration

```env
NADI_DRIVER=opentelemetry
NADI_OTEL_ENDPOINT=http://localhost:4318
NADI_OTEL_SERVICE_NAME=my-app
NADI_OTEL_SERVICE_VERSION=1.0.0
```

## Semantic Conventions

All entries include OpenTelemetry semantic convention attributes via `OpenTelemetrySemanticConventions`:

### Laravel-Specific

- `laravel.route.name` - Route name
- `laravel.route.action` - Controller action
- `laravel.job.queue` - Queue name
- `laravel.job.connection` - Queue connection
- `laravel.command.name` - Artisan command name

### Database

- `db.system` - Database system (mysql, pgsql, etc.)
- `db.connection.name` - Connection name
- `db.operation` - Operation type
- `db.statement` - SQL statement

### HTTP

- `http.request.method` - Request method
- `http.request.content_type` - Request content type
- `http.response.status_code` - Response status
- `http.response.content_type` - Response content type

### User/Session

- `user.id` - User identifier
- `user.email` - User email
- `session.id` - Session identifier

## Middleware

The OpenTelemetry middleware creates spans and propagates trace context.

### Auto-Registration

```env
NADI_OTEL_AUTO_INSTRUMENT_WEB=true
NADI_OTEL_AUTO_INSTRUMENT_API=true
```

### Manual Registration

```php
// In RouteServiceProvider or routes file
Route::middleware(['nadi.otel'])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
});
```

### Middleware Alias

The middleware is registered as `nadi.otel`:

```php
$router->aliasMiddleware('nadi.otel', OpenTelemetryMiddleware::class);
```

## Resource Attributes

Default resource attributes:

```php
'resource_attributes' => [
    'telemetry.sdk.name' => 'nadi-laravel',
    'telemetry.sdk.language' => 'php',
    'telemetry.sdk.version' => '1.0.0',
],
```

## Trace Sampling

Control trace sampling independent of event sampling:

```env
NADI_OTEL_TRACE_SAMPLING_RATIO=1.0  # Sample all traces
NADI_OTEL_TRACE_PARENT_BASED=true   # Honor parent span sampling
```

## Next Steps

- [Custom Handlers](02-custom-handlers.md) - Create your own handlers
