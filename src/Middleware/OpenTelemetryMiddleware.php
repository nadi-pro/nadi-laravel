<?php

namespace Nadi\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * OpenTelemetry Middleware for Laravel
 *
 * This middleware provides automatic trace context propagation and enhanced
 * request tracking with proper OpenTelemetry integration. It creates spans
 * for HTTP requests and automatically propagates trace context.
 */
class OpenTelemetryMiddleware
{
    protected ?TracerProvider $tracerProvider = null;

    public function __construct()
    {
        // Only initialize if OpenTelemetry driver is configured
        if (config('nadi.driver') === 'opentelemetry') {
            $this->initializeTracerProvider();
        }
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip if not using OpenTelemetry driver
        if (! $this->tracerProvider) {
            return $next($request);
        }

        // Extract trace context from incoming headers
        $context = $this->extractTraceContext($request);

        // Create a new span for this request
        $tracer = $this->tracerProvider->getTracer('nadi-laravel-middleware');
        $spanBuilder = $tracer->spanBuilder($this->generateSpanName($request))
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setParent($context);

        $span = $spanBuilder->startSpan();
        $scope = $span->activate();

        try {
            // Add HTTP semantic convention attributes
            $this->addHttpAttributes($span, $request);

            // Add user context if available
            $this->addUserAttributes($span);

            // Process the request
            $response = $next($request);

            // Add response attributes
            $this->addResponseAttributes($span, $response);

            // Set span status based on response
            $this->setSpanStatus($span, $response);

            // Add trace context to response headers
            $this->injectTraceContext($response);

            return $response;
        } catch (\Throwable $exception) {
            // Record exception and set error status
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());

            // Add exception attributes using semantic conventions
            $exceptionAttributes = OpenTelemetrySemanticConventions::exceptionAttributes($exception);
            foreach ($exceptionAttributes as $key => $value) {
                $span->setAttribute($key, $value);
            }

            throw $exception;
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    /**
     * Initialize the tracer provider
     */
    private function initializeTracerProvider(): void
    {
        try {
            // Get the transporter instance which should have the configured TracerProvider
            $transporter = app('nadi');

            // If the transporter is using OpenTelemetry driver, get its tracer provider
            if (method_exists($transporter, 'getTracerProvider')) {
                $this->tracerProvider = $transporter->getTracerProvider();
            }
        } catch (\Throwable $e) {
            // Silently fail if TracerProvider is not available
            $this->tracerProvider = null;
        }
    }

    /**
     * Extract trace context from request headers
     */
    private function extractTraceContext(Request $request): Context
    {
        $carrier = [];

        // Extract standard OpenTelemetry headers
        foreach ($request->headers->all() as $name => $values) {
            $carrier[strtolower($name)] = $values[0] ?? '';
        }

        return TraceContextPropagator::getInstance()->extract($carrier);
    }

    /**
     * Generate span name for the request
     */
    private function generateSpanName(Request $request): string
    {
        $route = $request->route();

        if ($route && $route->getName()) {
            return $route->getName();
        }

        if ($route && $route->getActionName()) {
            $action = $route->getActionName();
            if (strpos($action, '@') !== false) {
                return explode('@', $action)[1] ?? $action;
            }

            return $action;
        }

        return $request->method().' '.$request->getPathInfo();
    }

    /**
     * Add HTTP attributes to span using semantic conventions
     */
    private function addHttpAttributes($span, Request $request): void
    {
        $attributes = OpenTelemetrySemanticConventions::httpAttributes($request);

        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $span->setAttribute($key, $value);
            }
        }

        // Add additional request attributes
        $span->setAttribute('http.request.size', $request->server('CONTENT_LENGTH') ?: 0);

        if ($contentType = $request->header('Content-Type')) {
            $span->setAttribute('http.request.content_type', $contentType);
        }
    }

    /**
     * Add user attributes to span
     */
    private function addUserAttributes($span): void
    {
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        $allAttributes = array_merge($userAttributes, $sessionAttributes);

        foreach ($allAttributes as $key => $value) {
            if ($value !== null) {
                $span->setAttribute($key, $value);
            }
        }
    }

    /**
     * Add response attributes to span
     */
    private function addResponseAttributes($span, SymfonyResponse $response): void
    {
        $span->setAttribute(OpenTelemetrySemanticConventions::HTTP_STATUS_CODE, $response->getStatusCode());

        if ($contentLength = $response->headers->get('Content-Length')) {
            $span->setAttribute('http.response.size', (int) $contentLength);
        } else {
            // Estimate response size if Content-Length header is not set
            $content = $response->getContent();
            if (is_string($content)) {
                $span->setAttribute('http.response.size', strlen($content));
            }
        }

        if ($contentType = $response->headers->get('Content-Type')) {
            $span->setAttribute('http.response.content_type', $contentType);
        }
    }

    /**
     * Set span status based on response
     */
    private function setSpanStatus($span, SymfonyResponse $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            $span->setStatus(StatusCode::STATUS_ERROR, "HTTP {$statusCode}");
        } else {
            $span->setStatus(StatusCode::STATUS_OK);
        }
    }

    /**
     * Inject trace context into response headers
     */
    private function injectTraceContext(SymfonyResponse $response): void
    {
        try {
            $carrier = [];
            TraceContextPropagator::getInstance()->inject($carrier, null, Context::getCurrent());

            foreach ($carrier as $name => $value) {
                $response->headers->set($name, $value);
            }
        } catch (\Throwable $e) {
            // Silently ignore trace context injection errors
        }
    }
}
