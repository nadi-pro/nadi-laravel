<?php

namespace Nadi\Laravel\Metric;

use Nadi\Metric\Base;

class Network extends Base
{
    public function metrics(): array
    {
        return [
            'net.host.name' => request()->getHost(),
            'net.host.port' => request()->getPort(),
            'net.protocol.name' => app()->runningInConsole() ? 'CLI' : 'HTTP',
            'net.protocol.version' => ! app()->runningInConsole() ? request()->getProtocolVersion() : '',
        ];
    }
}
