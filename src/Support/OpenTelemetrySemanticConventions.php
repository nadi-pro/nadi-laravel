<?php

namespace Nadi\Laravel\Support;

use Nadi\Support\OpenTelemetrySemanticConventions as CoreConventions;

/**
 * OpenTelemetry Semantic Conventions for Laravel applications
 *
 * This class extends the core semantic conventions from nadi-php
 * and adds Laravel-specific constants and utility methods.
 *
 * @see https://opentelemetry.io/docs/specs/semconv/
 */
class OpenTelemetrySemanticConventions extends CoreConventions
{
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

    // Database-specific Laravel conventions
    public const DB_CONNECTION_NAME = 'db.connection.name';

    // HTTP-specific Laravel conventions
    public const HTTP_CLIENT_DURATION = 'http.client.duration';

    public const HTTP_QUERY = 'http.query';

    public const HTTP_HEADERS = 'http.headers';

    public const HTTP_REQUEST_CONTENT_TYPE = 'http.request.content_type';

    public const HTTP_RESPONSE_CONTENT_TYPE = 'http.response.content_type';

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
     * Delegates to parent class and adds any Laravel-specific context
     */
    public static function exceptionAttributes(\Throwable $exception): array
    {
        return parent::exceptionAttributes($exception);
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
     * Delegates to parent class
     */
    public static function performanceAttributes(float $startTime, ?int $memoryPeak = null): array
    {
        return parent::performanceAttributes($startTime, $memoryPeak);
    }
}
