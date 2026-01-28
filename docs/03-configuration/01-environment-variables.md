# Environment Variables

Complete reference of all configuration options.

## Core Settings

| Variable       | Default | Description                                        |
| -------------- | ------- | -------------------------------------------------- |
| `NADI_ENABLED` | `true`  | Master toggle for monitoring                       |
| `NADI_DRIVER`  | `log`   | Transport driver: `log`, `http`, `opentelemetry`   |

## HTTP Driver

| Variable           | Default               | Description                    |
| ------------------ | --------------------- | ------------------------------ |
| `NADI_API_KEY`     | -                     | Sanctum personal access token  |
| `NADI_APP_KEY`     | -                     | Application identifier token   |
| `NADI_ENDPOINT`    | `https://api.nadi.pro`| Nadi API endpoint              |
| `NADI_API_VERSION` | `v1`                  | API version                    |

## Log Driver

| Variable            | Default         | Description                       |
| ------------------- | --------------- | --------------------------------- |
| `NADI_STORAGE_PATH` | `storage/nadi/` | Local storage path for log files  |

## OpenTelemetry Driver

| Variable                             | Default                 | Description                                    |
| ------------------------------------ | ----------------------- | ---------------------------------------------- |
| `NADI_OTEL_ENDPOINT`                 | `http://localhost:4318` | OTLP endpoint                                  |
| `NADI_OTEL_SERVICE_NAME`             | App name                | Service name for traces                        |
| `NADI_OTEL_SERVICE_VERSION`          | `1.0.0`                 | Service version                                |
| `NADI_OTEL_SERVICE_NAMESPACE`        | -                       | Service namespace                              |
| `NADI_OTEL_SERVICE_INSTANCE_ID`      | -                       | Instance identifier                            |
| `NADI_OTEL_DEPLOYMENT_ENVIRONMENT`   | App env                 | Deployment environment                         |
| `NADI_OTEL_SUPPRESS_ERRORS`          | `true`                  | Suppress OTEL errors                           |
| `NADI_OTEL_PROTOCOL`                 | `http/protobuf`         | Protocol: `http/protobuf`, `grpc`, `http/json` |
| `NADI_OTEL_TIMEOUT`                  | `10`                    | Request timeout in seconds                     |
| `NADI_OTEL_COMPRESSION`              | `gzip`                  | Compression: `gzip`, `none`                    |
| `NADI_OTEL_TRACE_SAMPLING_RATIO`     | `1.0`                   | Trace sampling ratio (0.0 to 1.0)              |
| `NADI_OTEL_TRACE_PARENT_BASED`       | `true`                  | Use parent-based sampling                      |
| `NADI_OTEL_AUTO_INSTRUMENT_WEB`      | `false`                 | Auto-add middleware to web routes              |
| `NADI_OTEL_AUTO_INSTRUMENT_API`      | `false`                 | Auto-add middleware to API routes              |

## Query Monitoring

| Variable                    | Default | Description                           |
| --------------------------- | ------- | ------------------------------------- |
| `NADI_QUERY_SLOW_THRESHOLD` | `500`   | Slow query threshold in milliseconds  |

## Sampling

| Variable                         | Default      | Description                                                      |
| -------------------------------- | ------------ | ---------------------------------------------------------------- |
| `NADI_SAMPLING_STRATEGY`         | `fixed_rate` | Strategy: `fixed_rate`, `dynamic_rate`, `interval`, `peak_load`  |
| `NADI_SAMPLING_RATE`             | `0.1`        | Fixed rate sampling (10% default)                                |
| `NADI_SAMPLING_BASE_RATE`        | `0.05`       | Base rate for dynamic sampling                                   |
| `NADI_SAMPLING_LOAD_FACTOR`      | `1.0`        | Load factor for dynamic sampling                                 |
| `NADI_SAMPLING_INTERVAL_SECONDS` | `60`         | Interval for interval sampling                                   |

## Next Steps

- [Transport Drivers](02-transport-drivers.md) - Driver-specific configuration
- [Sampling Strategies](03-sampling-strategies.md) - Data collection control
