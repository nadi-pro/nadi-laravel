<?php

namespace Nadi\Laravel;

use InvalidArgumentException;
use Nadi\Sampling\Config;
use Nadi\Sampling\Contract as SamplingContract;
use Nadi\Sampling\DynamicRateSampling;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\IntervalSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Transporter\Contract;
use Nadi\Transporter\Service;

class Transporter
{
    protected string $driver;

    protected Contract $transporter;
    protected SamplingManager $samplingManager;
    protected Service $service;
    protected $data;

    public function __construct()
    {
        $this->configureTransporter();

        $this->configureSampling();

        $this->service = new Service($this->transporter, $this->samplingManager);
    }

    private function configureTransporter()
    {
        $this->driver = '\\Nadi\\Transporter\\'.ucfirst(config('nadi.driver'));

        if (! class_exists($this->driver)) {
            throw new \Exception("$this->driver did not exists");
        }

        if (! in_array(Contract::class, class_implements($this->driver))) {
            throw new \Exception("$this->driver did not implement the \Nadi\Transporter\Contract class.");
        }

        $this->transporter = (new $this->driver)
            ->configure(
                config('nadi.connections.'.config('nadi.driver'))
            );
    }

    private function configureSampling()
    {
        $config = new Config(
            samplingRate: config('nadi.sampling.config.sampling_rate'),
            baseRate: config('nadi.sampling.config.base_rate'),
            loadFactor: config('nadi.sampling.config.load_factor'),
            intervalSeconds: config('nadi.sampling.config.interval_seconds')
        );

        $strategies = config('nadi.sampling.strategies');

        $strategy = config('nadi.sampling.strategy');

        $class = ! isset($strategies[$strategy])
            ? FixedRateSampling::class
            : $strategies[$strategy];

        if(! in_array(\Nadi\Sampling\Contract::class, class_implements($class))) {
            throw new \Exception("$class not implement \Nadi\Sampling\Contract", 500);
        }

        $this->samplingManager = new SamplingManager(new $class($config));
    }

    public static function make()
    {
        return new self();
    }

    public function store(array $data)
    {
        return $this->service->handle($data);
    }

    public function send()
    {
       return $this->service->send();
    }

    public function test()
    {
        return $this->service->test();
    }

    public function verify()
    {
        return $this->service->verify();
    }

    public function __destruct()
    {
        return $this->service->send();
    }
}
