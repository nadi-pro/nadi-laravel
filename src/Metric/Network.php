<?php

namespace Nadi\Laravel\Metric;

use Nadi\Metric\Base;

class Network extends Base
{
    public function metrics(): array
    {
        // Return empty array in console context (base Network metric will still be used)
        if (! function_exists('request') || ! request() || app()->runningInConsole()) {
            return [];
        }

        return [
            'net.host.name' => request()->getHost(),
            'net.host.port' => request()->getPort(),
            'net.protocol.name' => 'HTTP',
            'net.protocol.version' => request()->getProtocolVersion(),
        ];
    }
}
