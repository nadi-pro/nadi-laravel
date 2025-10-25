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
            'key' => env('NADI_KEY'),
            'token' => env('NADI_TOKEN'),
            'endpoint' => env('NADI_ENDPOINT', 'https://api.nadi.pro'),
        ],
        'opentelemetry' => [
            'endpoint' => env('NADI_OTEL_ENDPOINT', 'http://localhost:4318'),
            'service_name' => env('NADI_OTEL_SERVICE_NAME', config('app.name', 'laravel-app')),
            'service_version' => env('NADI_OTEL_SERVICE_VERSION', '1.0.0'),
            'suppress_errors' => env('NADI_OTEL_SUPPRESS_ERRORS', true),
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
