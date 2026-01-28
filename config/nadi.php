<?php

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Queue\Events\JobFailed;
use Nadi\Laravel\Handler\HandleCommandEvent;
use Nadi\Laravel\Handler\HandleExceptionEvent;
use Nadi\Laravel\Handler\HandleFailedJobEvent;
use Nadi\Laravel\Handler\HandleHttpRequestEvent;
use Nadi\Laravel\Handler\HandleNotificationFailedEvent;
use Nadi\Laravel\Handler\HandleQueryExecutedEvent;

return [
    'enabled' => env('NADI_ENABLED', true),

    'driver' => env('NADI_DRIVER', 'log'),

    'connections' => [
        'log' => [
            'path' => env('NADI_STORAGE_PATH', storage_path('nadi/')),
        ],
        'http' => [
            'apiKey' => env('NADI_API_KEY'),      // Sanctum personal access token (Authorization: Bearer)
            'appKey' => env('NADI_APP_KEY'),      // Application identifier token (Nadi-App-Token header)
            'endpoint' => env('NADI_ENDPOINT', 'https://api.nadi.pro'),
            'version' => env('NADI_API_VERSION', 'v1'),
        ],
        'opentelemetry' => [
            'endpoint' => env('NADI_OTEL_ENDPOINT', 'http://localhost:4318'),
            'service_name' => env('NADI_OTEL_SERVICE_NAME', config('app.name', 'laravel-app')),
            'service_version' => env('NADI_OTEL_SERVICE_VERSION', '1.0.0'),
            'service_namespace' => env('NADI_OTEL_SERVICE_NAMESPACE'),
            'service_instance_id' => env('NADI_OTEL_SERVICE_INSTANCE_ID'),
            'deployment_environment' => env('NADI_OTEL_DEPLOYMENT_ENVIRONMENT', config('app.env', 'production')),
            'suppress_errors' => env('NADI_OTEL_SUPPRESS_ERRORS', true),
            'protocol' => env('NADI_OTEL_PROTOCOL', 'http/protobuf'), // http/protobuf, grpc, http/json
            'timeout' => env('NADI_OTEL_TIMEOUT', 10), // seconds
            'compression' => env('NADI_OTEL_COMPRESSION', 'gzip'), // gzip, none
            'headers' => [
                // Additional headers can be added via environment variables
                // Format: NADI_OTEL_HEADER_<KEY>=<VALUE>
            ],
            'trace_sampling' => [
                'ratio' => env('NADI_OTEL_TRACE_SAMPLING_RATIO', 1.0), // 0.0 to 1.0
                'parent_based' => env('NADI_OTEL_TRACE_PARENT_BASED', true),
            ],
            'resource_attributes' => [
                // Additional resource attributes
                'telemetry.sdk.name' => 'nadi-laravel',
                'telemetry.sdk.language' => 'php',
                'telemetry.sdk.version' => '1.0.0',
            ],
            'span_limits' => [
                'max_attributes_per_span' => env('NADI_OTEL_MAX_ATTRIBUTES_PER_SPAN', 128),
                'max_events_per_span' => env('NADI_OTEL_MAX_EVENTS_PER_SPAN', 128),
                'max_links_per_span' => env('NADI_OTEL_MAX_LINKS_PER_SPAN', 128),
                'max_attribute_value_length' => env('NADI_OTEL_MAX_ATTRIBUTE_VALUE_LENGTH', 4096),
            ],
            // Middleware configuration
            'auto_instrument_web' => env('NADI_OTEL_AUTO_INSTRUMENT_WEB', false),
            'auto_instrument_api' => env('NADI_OTEL_AUTO_INSTRUMENT_API', false),
        ],
    ],

    'observe' => [
        MessageLogged::class => [
            HandleExceptionEvent::class,
        ],
        QueryExecuted::class => [
            HandleQueryExecutedEvent::class,
        ],
        JobFailed::class => [
            HandleFailedJobEvent::class,
        ],
        RequestHandled::class => [
            HandleHttpRequestEvent::class,
        ],
        NotificationFailed::class => [
            HandleNotificationFailedEvent::class,
        ],
        CommandFinished::class => [
            HandleCommandEvent::class,
        ],
    ],

    'query' => [
        'slow-threshold' => env('NADI_QUERY_SLOW_THRESHOLD', 500), // in miliseconds.
    ],

    'http' => [
        'hidden_request_headers' => [
            'authorization',
            'php-auth-pw',
        ],
        'hidden_parameters' => [
            'password',
            'password_confirmation',
        ],
        'hidden_response_parameters' => [],
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
        'ignored_status_codes' => [
            100, 101, 102, 103,
            200, 201, 202, 203, 204, 205, 206, 207,
            300, 302, 303, 304, 305, 306, 307, 308,
        ],
    ],

    'sampling' => [
        'strategy' => env('NADI_SAMPLING_STRATEGY', 'fixed_rate'), // The strategy to use: fixed_rate, dynamic_rate, interval
        'config' => [
            'sampling_rate' => env('NADI_SAMPLING_RATE', 0.1),       // 10% default rate
            'base_rate' => env('NADI_SAMPLING_BASE_RATE', 0.05),              // Base rate for dynamic sampling
            'load_factor' => env('NADI_SAMPLING_LOAD_FACTOR', 1.0),           // Load factor for dynamic sampling
            'interval_seconds' => env('NADI_SAMPLING_INTERVAL_SECONDS', 60),  // Interval in seconds for interval sampling
        ],
        'strategies' => [
            'dynamic_rate' => Nadi\Sampling\DynamicRateSampling::class,
            'fixed_rate' => Nadi\Sampling\FixedRateSampling::class,
            'interval' => Nadi\Sampling\IntervalSampling::class,
            'peak_load' => Nadi\Sampling\PeakLoadSampling::class,
        ],
    ],
];
