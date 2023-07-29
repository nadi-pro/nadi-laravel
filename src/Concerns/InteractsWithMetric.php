<?php

namespace Nadi\Laravel\Concerns;

use Nadi\Laravel\Metric\Application;
use Nadi\Laravel\Metric\Framework;
use Nadi\Laravel\Metric\Http;
use Nadi\Laravel\Metric\Network;

trait InteractsWithMetric
{
    public function registerMetrics()
    {
        if (method_exists($this, 'addMetric')) {
            $this->addMetric(new Http);
            $this->addMetric(new Framework);
            $this->addMetric(new Application);
            $this->addMetric(new Network);
        }
    }
}
