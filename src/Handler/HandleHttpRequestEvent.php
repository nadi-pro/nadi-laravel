<?php

namespace Nadi\Laravel\Handler;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Nadi\Data\Type;
use Nadi\Laravel\Actions\FormatModel;
use Nadi\Laravel\Data\Entry;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class HandleHttpRequestEvent extends Base
{
    /**
     * Record an incoming HTTP request.
     *
     * @return void
     */
    public function handle(RequestHandled $event)
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $uri = str_replace($event->request->root(), '', $event->request->fullUrl()) ?: '/';
        $status_code = $event->response->getStatusCode();
        $method = $event->request->method();
        $title = "$uri returned HTTP Status Code $status_code";

        if (in_array($status_code, config('nadi.http.ignored_status_codes'))) {
            return;
        }

        // Generate OpenTelemetry semantic convention attributes
        $otelAttributes = OpenTelemetrySemanticConventions::httpAttributes($event->request, $event->response);

        // Add user context if available
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();

        // Add session context if available
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        // Add performance attributes
        $performanceAttributes = OpenTelemetrySemanticConventions::performanceAttributes(
            $startTime,
            memory_get_peak_usage(true)
        );

        // Merge all OpenTelemetry attributes
        $otelData = array_merge($otelAttributes, $userAttributes, $sessionAttributes, $performanceAttributes);

        // Add view information if available
        if ($event->response instanceof IlluminateResponse && $event->response->getOriginalContent() instanceof View) {
            $view = $event->response->getOriginalContent();
            $otelData['laravel.view'] = $view->getPath();
        }

        $entryData = [
            'title' => $title,
            'description' => "$uri for $method request returned HTTP Status Code $status_code",
            'uri' => $uri,
            'method' => $method,
            'controller_action' => optional($event->request->route())->getActionName(),
            'middleware' => array_values(optional($event->request->route())->gatherMiddleware() ?? []),
            'headers' => $this->headers($event->request->headers->all()),
            'payload' => $this->payload($this->input($event->request)),
            'session' => $this->payload($this->sessionVariables($event->request)),
            'response_status' => $status_code,
            'response' => $this->response($event->response),
            'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1025, 1),
            // Add OpenTelemetry semantic convention data
            'otel' => $otelData,
        ];

        $this->store(Entry::make(
            Type::HTTP,
            $entryData
        )->setHashFamily(
            $this->hash($method.$status_code.$event->request->fullUrl().date('Y-m-d H'))
        )->tags($this->generateTags($event))->toArray());
    }

    /**
     * Generate tags for the HTTP request event
     */
    protected function generateTags(RequestHandled $event): array
    {
        $tags = [
            $event->request->method(),
            $event->response->getStatusCode(),
        ];

        // Add OpenTelemetry standard tags
        $tags[] = 'http.method:'.$event->request->method();
        $tags[] = 'http.status_code:'.$event->response->getStatusCode();

        // Add route-specific tags
        if ($route = $event->request->route()) {
            if ($routeName = $route->getName()) {
                $tags[] = 'laravel.route.name:'.$routeName;
            }

            if ($action = $route->getActionName()) {
                $tags[] = 'laravel.route.action:'.$action;

                // Extract controller name
                if (strpos($action, '@') !== false) {
                    $controller = explode('@', $action)[0];
                    $tags[] = 'laravel.controller:'.$controller;
                }
            }
        }

        // Add user context if available
        try {
            if (app()->bound('auth') && app('auth')->hasUser()) {
                $user = app('auth')->user();
                if ($user) {
                    $tags[] = 'user.id:'.$user->getAuthIdentifier();
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore auth errors
        }

        return $tags;
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headers = collect($headers)->map(function ($header) {
            return $header[0];
        })->toArray();

        return $this->hideParameters($headers,
            config('nadi.http.hidden_request_headers')
        );
    }

    /**
     * Format the given payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function payload($payload)
    {
        return $this->hideParameters($payload,
            config('nadi.http.hidden_parameters')
        );
    }

    /**
     * Hide the given parameters.
     *
     * @param  array  $data
     * @param  array  $hidden
     * @return mixed
     */
    protected function hideParameters($data, $hidden)
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }

    /**
     * Extract the session variables from the given request.
     *
     * @return array
     */
    private function sessionVariables(Request $request)
    {
        return $request->hasSession() ? $request->session()->all() : [];
    }

    /**
     * Extract the input from the given request.
     *
     * @return array
     */
    private function input(Request $request)
    {
        $files = $request->files->all();

        array_walk_recursive($files, function (&$file) {
            $file = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->isFile() ? ($file->getSize() / 1000).'KB' : '0',
            ];
        });

        return array_replace_recursive($request->input(), $files);
    }

    /**
     * Format the given response object.
     *
     * @return array|string
     */
    protected function response(Response $response)
    {
        $content = $response->getContent();

        if (is_string($content)) {
            if (is_array(json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE) {
                return $this->contentWithinLimits($content)
                        ? $this->hideParameters(json_decode($content, true), config('nadi.http.hidden_response_parameters'))
                        : 'Purged By Nadi';
            }

            if (Str::startsWith(strtolower($response->headers->get('Content-Type')), 'text/plain')) {
                return $this->contentWithinLimits($content) ? $content : 'Purged By Nadi';
            }
        }

        if ($response instanceof RedirectResponse) {
            return 'Redirected to '.$response->getTargetUrl();
        }

        if ($response instanceof IlluminateResponse && $response->getOriginalContent() instanceof View) {
            return [
                'view' => $response->getOriginalContent()->getPath(),
                'data' => $this->extractDataFromView($response->getOriginalContent()),
            ];
        }

        return 'HTML Response';
    }

    /**
     * Determine if the content is within the set limits.
     *
     * @param  string  $content
     * @return bool
     */
    public function contentWithinLimits($content)
    {
        $limit = data_get($this, 'options.size_limit', 64);

        return mb_strlen($content) / 1000 <= $limit;
    }

    /**
     * Extract the data from the given view in array form.
     *
     * @param  \Illuminate\View\View  $view
     * @return array
     */
    protected function extractDataFromView($view)
    {
        return collect($view->getData())->map(function ($value) {
            if ($value instanceof Model) {
                return FormatModel::given($value);
            } elseif (is_object($value)) {
                return [
                    'class' => get_class($value),
                    'properties' => json_decode(json_encode($value), true),
                ];
            } else {
                return json_decode(json_encode($value), true);
            }
        })->toArray();
    }
}
