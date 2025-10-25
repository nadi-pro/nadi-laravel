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
        $metrics['http.client.duration'] = $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        // Add query string using OTel convention
        if ($queryString = $request->getQueryString()) {
            $metrics['http.query'] = $queryString;
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

        $metrics['http.headers'] = Arr::undot($headers);

        return $metrics;
    }
}
