<?php

namespace Nadi\Laravel\Handler;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Nadi\Data\Type;
use Nadi\Laravel\Actions\ExceptionContext;
use Nadi\Laravel\Actions\ExtractTags;
use Nadi\Laravel\Data\ExceptionEntry;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Throwable;

class HandleExceptionEvent extends Base
{
    /**
     * Handle the event.
     */
    public function handle(MessageLogged $event): void
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        $exception = $event->context['exception'];

        $trace = collect($exception->getTrace())->map(function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        // Prepare OpenTelemetry semantic convention attributes
        $otelAttributes = OpenTelemetrySemanticConventions::exceptionAttributes($exception);

        // Add user context if available
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();

        // Add session context if available
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        // Merge all OpenTelemetry attributes
        $otelData = array_merge($otelAttributes, $userAttributes, $sessionAttributes);

        $entryData = [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
            'context' => transform(Arr::except($event->context, ['exception', 'telescope']), function ($context) {
                return ! empty($context) ? $context : null;
            }),
            'trace' => $trace,
            'line_preview' => ExceptionContext::get($exception),
            // Add OpenTelemetry semantic convention data
            'otel' => $otelData,
        ];

        // Add request context if available
        if (function_exists('request') && request()) {
            $httpAttributes = OpenTelemetrySemanticConventions::httpAttributes(request());
            $entryData['otel'] = array_merge($entryData['otel'], $httpAttributes);
        }

        $this->store(
            ExceptionEntry::make(
                $exception,
                Type::EXCEPTION,
                $entryData
            )->setHashFamily(
                $this->hash(
                    get_class($exception).
                    $exception->getFile().
                    $exception->getLine().
                    $exception->getMessage().
                    date('Y-m-d'))
            )->tags($this->tags($event))->toArray()
        );
    }

    /**
     * Extract the tags for the given event.
     *
     * @param  \Illuminate\Log\Events\MessageLogged  $event
     * @return array
     */
    protected function tags($event)
    {
        $tags = array_merge(ExtractTags::from($event->context['exception']),
            $event->context['telescope'] ?? []
        );

        // Add OpenTelemetry standard tags
        $exception = $event->context['exception'];
        $tags[] = 'exception.type:'.get_class($exception);
        $tags[] = 'error.type:'.get_class($exception);

        // Add Laravel-specific tags if request is available
        if (function_exists('request') && request() && request()->route()) {
            $route = request()->route();
            if ($routeName = $route->getName()) {
                $tags[] = 'laravel.route.name:'.$routeName;
            }
            if ($action = $route->getActionName()) {
                $tags[] = 'laravel.route.action:'.$action;
            }
        }

        return $tags;
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return ! isset($event->context['exception']) ||
            ! $event->context['exception'] instanceof Throwable;
    }
}
