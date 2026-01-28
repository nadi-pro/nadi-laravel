# HTTP Handler

Monitors HTTP requests with error status codes.

## Event

Listens to: `Illuminate\Foundation\Http\Events\RequestHandled`

## What's Captured

- Request method and URL
- Status code
- Request headers (filtered)
- Request parameters (filtered)
- Response content type
- Request timing

## Status Code Filtering

By default, success codes are ignored:

```php
// config/nadi.php
'http' => [
    'ignored_status_codes' => [
        100, 101, 102, 103,           // Informational
        200, 201, 202, 203, 204, ...  // Success
        300, 302, 303, 304, ...       // Redirection
    ],
]
```

Only 4xx and 5xx responses are captured.

## Hidden Headers

Sensitive headers are redacted:

```php
'hidden_request_headers' => [
    'authorization',
    'php-auth-pw',
],
```

## Hidden Parameters

Sensitive parameters are redacted:

```php
'hidden_parameters' => [
    'password',
    'password_confirmation',
],
```

## Configuration

```php
// config/nadi.php
'http' => [
    'hidden_request_headers' => ['authorization', 'php-auth-pw'],
    'hidden_parameters' => ['password', 'password_confirmation'],
    'hidden_response_parameters' => [],
    'ignored_status_codes' => [200, 201, ...],
],
```

## Next Steps

- [Custom Handlers](../05-advanced/02-custom-handlers.md) - Create your own handlers
