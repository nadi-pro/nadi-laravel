<?php

namespace Nadi\Laravel\Metric;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Metric\Base;
use Nadi\Support\Arr;

class Http extends Base
{
    public function metrics(): array
    {
        if (! function_exists('request') || ! request()) {
            return [];
        }

        $request = request();
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');

        // Use OpenTelemetry semantic conventions as base
        $metrics = OpenTelemetrySemanticConventions::httpAttributes($request);

        // Add performance metrics
        $metrics[OpenTelemetrySemanticConventions::HTTP_CLIENT_DURATION] = $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        // Add query string using OTel convention
        if ($queryString = $request->getQueryString()) {
            $metrics[OpenTelemetrySemanticConventions::HTTP_QUERY] = $queryString;
        }

        // Add headers with filtered sensitive data
        $headers = collect($request->headers->all())
            ->map(function ($header) {
                return $header[0];
            })
            ->reject(function ($header, $key) {
                return in_array($key, [
                    'authorization', config('nadi.header-key'), 'nadi-key',
                ]);
            })
            ->toArray();

        $metrics[OpenTelemetrySemanticConventions::HTTP_HEADERS] = Arr::undot($headers);

        return $metrics;
    }
}
