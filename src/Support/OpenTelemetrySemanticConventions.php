<?php

namespace Nadi\Laravel\Support;

/**
 * OpenTelemetry Semantic Conventions for Laravel applications
 *
 * This class provides constants and utility methods for OpenTelemetry semantic conventions
 * specific to Laravel applications, ensuring consistent attribute naming across the codebase.
 *
 * @see https://opentelemetry.io/docs/specs/semconv/
 */
class OpenTelemetrySemanticConventions
{
    // HTTP Semantic Conventions
    public const HTTP_METHOD = 'http.method';

    public const HTTP_URL = 'http.url';

    public const HTTP_SCHEME = 'http.scheme';

    public const HTTP_HOST = 'http.host';

    public const HTTP_TARGET = 'http.target';

    public const HTTP_STATUS_CODE = 'http.status_code';

    public const HTTP_REQUEST_SIZE = 'http.request.size';

    public const HTTP_RESPONSE_SIZE = 'http.response.size';

    public const HTTP_USER_AGENT = 'http.user_agent';

    public const HTTP_ROUTE = 'http.route';

    public const HTTP_CLIENT_IP = 'http.client_ip';

    // Database Semantic Conventions
    public const DB_SYSTEM = 'db.system';

    public const DB_CONNECTION_STRING = 'db.connection_string';

    public const DB_USER = 'db.user';

    public const DB_NAME = 'db.name';

    public const DB_STATEMENT = 'db.statement';

    public const DB_OPERATION = 'db.operation';

    public const DB_SQL_TABLE = 'db.sql.table';

    public const DB_QUERY_DURATION = 'db.query.duration';

    // Exception Semantic Conventions
    public const EXCEPTION_TYPE = 'exception.type';

    public const EXCEPTION_MESSAGE = 'exception.message';

    public const EXCEPTION_STACKTRACE = 'exception.stacktrace';

    public const EXCEPTION_ESCAPED = 'exception.escaped';

    // Error Semantic Conventions
    public const ERROR_TYPE = 'error.type';

    public const ERROR_MESSAGE = 'error.message';

    // Code Semantic Conventions
    public const CODE_FUNCTION = 'code.function';

    public const CODE_NAMESPACE = 'code.namespace';

    public const CODE_FILEPATH = 'code.filepath';

    public const CODE_LINENO = 'code.lineno';

    public const CODE_COLUMN = 'code.column';

    // Service/Resource Semantic Conventions
    public const SERVICE_NAME = 'service.name';

    public const SERVICE_NAMESPACE = 'service.namespace';

    public const SERVICE_INSTANCE_ID = 'service.instance.id';

    public const SERVICE_VERSION = 'service.version';

    public const DEPLOYMENT_ENVIRONMENT = 'deployment.environment';

    // Laravel-specific Conventions
    public const LARAVEL_ROUTE_NAME = 'laravel.route.name';

    public const LARAVEL_ROUTE_ACTION = 'laravel.route.action';

    public const LARAVEL_CONTROLLER = 'laravel.controller';

    public const LARAVEL_MIDDLEWARE = 'laravel.middleware';

    public const LARAVEL_VIEW = 'laravel.view';

    public const LARAVEL_ARTISAN_COMMAND = 'laravel.artisan.command';

    public const LARAVEL_JOB_CLASS = 'laravel.job.class';

    public const LARAVEL_JOB_QUEUE = 'laravel.job.queue';

    public const LARAVEL_NOTIFICATION_CLASS = 'laravel.notification.class';

    public const LARAVEL_NOTIFICATION_CHANNEL = 'laravel.notification.channel';

    // User Semantic Conventions
    public const USER_ID = 'user.id';

    public const USER_NAME = 'user.name';

    public const USER_EMAIL = 'user.email';

    // Session Semantic Conventions
    public const SESSION_ID = 'session.id';

    // Performance Semantic Conventions
    public const MEMORY_USAGE = 'memory.usage';

    public const DURATION = 'duration';

    /**
     * Get HTTP attributes from Laravel request
     */
    public static function httpAttributes(\Illuminate\Http\Request $request, ?\Symfony\Component\HttpFoundation\Response $response = null): array
    {
        $attributes = [
            self::HTTP_METHOD => $request->method(),
            self::HTTP_URL => $request->fullUrl(),
            self::HTTP_SCHEME => $request->getScheme(),
            self::HTTP_HOST => $request->getHost(),
            self::HTTP_TARGET => $request->getRequestUri(),
        ];

        // Add user agent if available
        if ($userAgent = $request->userAgent()) {
            $attributes[self::HTTP_USER_AGENT] = $userAgent;
        }

        // Add route information if available
        if ($route = $request->route()) {
            if ($routeName = $route->getName()) {
                $attributes[self::LARAVEL_ROUTE_NAME] = $routeName;
            }

            if ($action = $route->getActionName()) {
                $attributes[self::LARAVEL_ROUTE_ACTION] = $action;
            }

            // Extract controller name
            if (strpos($action, '@') !== false) {
                $attributes[self::LARAVEL_CONTROLLER] = explode('@', $action)[0];
            }

            // Add middleware
            $middleware = $route->gatherMiddleware();
            if (! empty($middleware)) {
                $attributes[self::LARAVEL_MIDDLEWARE] = implode(',', $middleware);
            }
        }

        // Add client IP
        if ($clientIp = $request->getClientIp()) {
            $attributes[self::HTTP_CLIENT_IP] = $clientIp;
        }

        // Add response attributes if available
        if ($response) {
            $attributes[self::HTTP_STATUS_CODE] = $response->getStatusCode();
        }

        return $attributes;
    }

    /**
     * Get exception attributes from throwable
     */
    public static function exceptionAttributes(\Throwable $exception): array
    {
        return [
            self::EXCEPTION_TYPE => get_class($exception),
            self::EXCEPTION_MESSAGE => $exception->getMessage(),
            self::EXCEPTION_STACKTRACE => $exception->getTraceAsString(),
            self::CODE_FILEPATH => $exception->getFile(),
            self::CODE_LINENO => $exception->getLine(),
            self::ERROR_TYPE => get_class($exception),
            self::ERROR_MESSAGE => $exception->getMessage(),
        ];
    }

    /**
     * Get database attributes for query
     */
    public static function databaseAttributes(string $connectionName, string $query, float $duration): array
    {
        $config = config("database.connections.{$connectionName}");

        $attributes = [
            self::DB_SYSTEM => $config['driver'] ?? 'unknown',
            self::DB_STATEMENT => $query,
            self::DB_QUERY_DURATION => $duration,
        ];

        if (isset($config['database'])) {
            $attributes[self::DB_NAME] = $config['database'];
        }

        // Extract operation from query
        if (preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|TRUNCATE)\s+/i', $query, $matches)) {
            $attributes[self::DB_OPERATION] = strtoupper($matches[1]);
        }

        // Extract table name for SQL queries
        if (preg_match('/(?:FROM|INTO|UPDATE|TABLE)\s+`?(\w+)`?/i', $query, $matches)) {
            $attributes[self::DB_SQL_TABLE] = $matches[1];
        }

        return $attributes;
    }

    /**
     * Get user attributes from authenticated user
     */
    public static function userAttributes(): array
    {
        $attributes = [];

        try {
            if (app()->bound('auth') && app('auth')->hasUser()) {
                $user = app('auth')->user();

                if ($user) {
                    $attributes[self::USER_ID] = (string) $user->getAuthIdentifier();

                    if (method_exists($user, 'getAttribute')) {
                        if ($name = $user->getAttribute('name')) {
                            $attributes[self::USER_NAME] = $name;
                        }

                        if ($email = $user->getAttribute('email')) {
                            $attributes[self::USER_EMAIL] = $email;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore auth errors
        }

        return $attributes;
    }

    /**
     * Get session attributes
     */
    public static function sessionAttributes(): array
    {
        $attributes = [];

        if (function_exists('session') && session()->isStarted()) {
            $attributes[self::SESSION_ID] = session()->getId();
        }

        return $attributes;
    }

    /**
     * Get Laravel job attributes
     */
    public static function jobAttributes(string $jobClass, ?string $queue = null): array
    {
        $attributes = [
            self::LARAVEL_JOB_CLASS => $jobClass,
        ];

        if ($queue) {
            $attributes[self::LARAVEL_JOB_QUEUE] = $queue;
        }

        return $attributes;
    }

    /**
     * Get Laravel notification attributes
     */
    public static function notificationAttributes(string $notificationClass, string $channel): array
    {
        return [
            self::LARAVEL_NOTIFICATION_CLASS => $notificationClass,
            self::LARAVEL_NOTIFICATION_CHANNEL => $channel,
        ];
    }

    /**
     * Get Laravel artisan command attributes
     */
    public static function commandAttributes(string $command): array
    {
        return [
            self::LARAVEL_ARTISAN_COMMAND => $command,
        ];
    }

    /**
     * Get performance attributes
     */
    public static function performanceAttributes(float $startTime, ?int $memoryPeak = null): array
    {
        $attributes = [
            self::DURATION => round((microtime(true) - $startTime) * 1000, 2), // in milliseconds
        ];

        if ($memoryPeak !== null) {
            $attributes[self::MEMORY_USAGE] = $memoryPeak;
        }

        return $attributes;
    }
}
