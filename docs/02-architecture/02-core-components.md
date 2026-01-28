# Core Components

The main classes that make up Nadi Laravel.

## NadiServiceProvider

Location: `src/NadiServiceProvider.php`

The service provider bootstraps the package:

```php
// Publishes configuration
$this->publishes([
    __DIR__.'/../config/nadi.php' => config_path('nadi.php'),
], 'nadi-config');

// Registers transporter singleton
app()->singleton('nadi', function () {
    return \Nadi\Laravel\Transporter::make();
});

// Dynamically registers event listeners
foreach (config('nadi.observe') as $event => $listeners) {
    foreach ($listeners as $listener) {
        app()['events']->listen($event, $listener);
    }
}
```

Key behaviors:

- Registers console commands (`nadi:install`, `nadi:test`, `nadi:verify`)
- Binds transporter as singleton in service container
- Registers OpenTelemetry middleware when using OTEL driver
- Skips registration entirely when `NADI_ENABLED=false`

## Transporter

Location: `src/Transporter.php`

Wraps the core PHP SDK and configures it for Laravel:

- **`store()`** - Buffer monitored data entries
- **`send()`** - Flush buffered data to configured driver
- **`test()`** - Verify connectivity
- **`verify()`** - Validate configuration

Auto-flushes data on destruct to ensure buffered entries are sent even if `send()` isn't called explicitly.

## Handler Base Class

Location: `src/Handler/Base.php`

All event handlers extend this class. Common functionality:

- **`store()`** - Save entries via transporter singleton
- **`hash()`** - Generate hash family for grouping similar issues
- **`tags()`** - Extract searchable tags from events

## Entry Classes

Location: `src/Data/`

- **`Entry`** - Base entry class extending `Nadi\Data\Entry`
- **`ExceptionEntry`** - Extended exception entry with Laravel-specific metrics

Both support OpenTelemetry semantic convention attributes and custom tagging.

## Shipper

Location: `src/Shipper/Shipper.php`

Manages the Go binary that forwards log files to the Nadi API:

```php
$shipper = new Shipper();

// Check status
$shipper->isInstalled();
$shipper->getInstalledVersion();
$shipper->needsUpdate();

// Management
$shipper->install();
$shipper->update();
$shipper->uninstall();

// Operations
$shipper->send($configPath);
$shipper->test($configPath);
$shipper->verify($configPath);
```

## Next Steps

- [Handlers](../04-handlers/README.md) - Event handler details
