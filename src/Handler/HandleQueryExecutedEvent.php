<?php

namespace Nadi\Laravel\Handler;

use Illuminate\Database\Events\QueryExecuted;
use Nadi\Data\Type;
use Nadi\Laravel\Concerns\FetchesStackTrace;
use Nadi\Laravel\Data\Entry;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;

class HandleQueryExecutedEvent extends Base
{
    use FetchesStackTrace;

    /**
     * Handle the event.
     */
    public function handle(QueryExecuted $event): void
    {
        $time = $event->time;
        $slow = $time > config('nadi.query.slow-threshold');

        if (! $slow) {
            return;
        }

        // Generate OpenTelemetry semantic convention attributes
        $otelAttributes = OpenTelemetrySemanticConventions::databaseAttributes(
            $event->connectionName,
            $this->replaceBindings($event),
            $time
        );

        // Add user context if available
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();

        // Add session context if available
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        // Merge all OpenTelemetry attributes
        $otelData = array_merge($otelAttributes, $userAttributes, $sessionAttributes);

        if ($caller = $this->getCallerFromStackTrace()) {
            // Add code location to OTel data
            $otelData[OpenTelemetrySemanticConventions::CODE_FILEPATH] = $caller['file'];
            $otelData[OpenTelemetrySemanticConventions::CODE_LINENO] = $caller['line'];

            $entryData = [
                'connection' => $event->connectionName,
                'bindings' => $event->bindings,
                'sql' => $this->replaceBindings($event),
                'time' => number_format($time, 2, '.', ''),
                'slow' => true,
                'file' => $caller['file'],
                'line' => $caller['line'],
                // Add OpenTelemetry semantic convention data
                'otel' => $otelData,
            ];

            $this->store(
                Entry::make(Type::QUERY, $entryData)
                    ->setHashFamily($this->hash($event->sql.date('Y-m-d')))
                    ->tags($this->tags($event))
                    ->toArray()
            );
        }
    }

    /**
     * Extract the tags for the given event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function tags($event)
    {
        $tags = [];

        // Check if this is a slow query
        $slowThreshold = config('nadi.query.slow-threshold');
        if ($event->time >= $slowThreshold) {
            $tags[] = 'slow';
        }

        // Add OpenTelemetry standard tags
        $tags[] = OpenTelemetrySemanticConventions::DB_SYSTEM.':'.(config("database.connections.{$event->connectionName}.driver") ?? 'unknown');
        $tags[] = OpenTelemetrySemanticConventions::DB_CONNECTION_NAME.':'.$event->connectionName;

        // Extract and tag operation
        if (preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|TRUNCATE)\s+/i', $event->sql, $matches)) {
            $operation = strtoupper($matches[1]);
            $tags[] = OpenTelemetrySemanticConventions::DB_OPERATION.':'.$operation;
        }

        // Mark as slow query
        if ($event->time > $slowThreshold) {
            $tags[] = 'query.slow:true';
        }

        return $tags;
    }

    /**
     * Format the given bindings to strings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function formatBindings($event)
    {
        return $event->connection->prepareBindings($event->bindings);
    }

    /**
     * Replace the placeholders with the actual bindings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return string
     */
    public function replaceBindings($event)
    {
        $sql = $event->sql;

        foreach ($this->formatBindings($event) as $key => $binding) {
            $regex = is_numeric($key)
                ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

            if ($binding === null) {
                $binding = 'null';
            } elseif (! is_int($binding) && ! is_float($binding)) {
                $binding = $this->quoteStringBinding($event, $binding);
            }

            $sql = preg_replace($regex, $binding, $sql, 1);
        }

        return $sql;
    }

    /**
     * Add quotes to string bindings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @param  string  $binding
     * @return string
     */
    protected function quoteStringBinding($event, $binding)
    {
        try {
            return $event->connection->getPdo()->quote($binding);
        } catch (\PDOException $e) {
            throw_if($e->getCode() !== 'IM001', $e);
        }

        // Fallback when PDO::quote function is missing...
        $binding = \strtr($binding, [
            chr(26) => '\\Z',
            chr(8) => '\\b',
            '"' => '\"',
            "'" => "\'",
            '\\' => '\\\\',
        ]);

        return "'".$binding."'";
    }
}
